<?php

namespace App\Filament\Clusters\Products\Resources\ProductResource\RelationManagers;

use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

class ItemRelationManager extends RelationManager
{
    protected static string $relationship = 'Item';

    protected static ?string $title = 'Producto Tallas';

    protected static ?string $modelLabel = 'Talla';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('size_id')
                    ->label('Talla')
                    ->placeholder('Seleccione una talla')
                    ->relationship('size', 'name')
                    ->required()
                    ->unique(modifyRuleUsing: function (Unique $rule,Get $get) {
                        return $rule->where('product_id', $this->getOwnerRecord()->getKey())->where('size_id',$get('size_id'));
                    },ignoreRecord: true),
                    Forms\Components\TextInput::make('code')
                    ->label('Código de barra')
                    ->minLength(8)
                    ->maxLength(14)
                    ->rule('regex:/^[A-Z0-9\-_]+$/') // Solo mayúsculas, números, guiones y guión bajo
                    ->placeholder('Ingrese el código de barra')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('code', strtoupper($state)))
                    ->live(onBlur:true)
                    ->hint('8-14 caracteres, solo letras mayúsculas, números y guiones'),
            
                Forms\Components\Toggle::make('is_available')
                    ->label('Disponible')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle')
                    ->required(),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Producto por tallas')
            ->recordTitleAttribute('product_id')
            ->columns([
                Tables\Columns\TextColumn::make('size.name')
                    ->label('Talla')
                    ->sortable(),

                Tables\Columns\TextColumn::make('size.measurement')
                    ->label('Medida')
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ImageColumn::make('barcode')
                    ->disk('public')
                    ->visibility('public')
                    ->label('Código de barras'),

                Tables\Columns\ToggleColumn::make('is_available')
                    ->label('Disponible')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle')
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
                Tables\Filters\SelectFilter::make('activos')
                    ->options([
                        true => 'Disponibles',
                        false => 'No Disponibles'
                    ])->attribute('is_available')
                    ->native(false)
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                ->label('Registrar nueva Talla')
                ->using(function (array $data, string $model): Model {
                    // 1️⃣ Generar o tomar el code
                    $code = $data['code'] ?? 'PROD-' . uniqid();
            
                    // 2️⃣ Generar la imagen del código de barras
                    $generator = new BarcodeGeneratorPNG();
                    $barcodeImage = $generator->getBarcode($code, $generator::TYPE_CODE_128);
            
                    // 3️⃣ Definir ruta única para todas las imágenes
                    $storagePath = 'products/barcodes'; // Carpeta única para todas
                    $filename = "{$code}.png";          // Nombre según el code
                    $fullPath = "{$storagePath}/{$filename}";
            
                    // 4️⃣ Guardar imagen en disco público
                    Storage::disk('public')->put($fullPath, $barcodeImage);
            
                    // 5️⃣ Guardar datos en base
                    $data['product_id'] = $this->getOwnerRecord()->id;
                    $data['code'] = $code;
                    $data['barcode'] = $fullPath;
            
                    return $model::create($data);
                })
            
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->mutateFormDataUsing(function (array $data,Model $record): array {
                        
                            // Guardar el code anterior
                            $oldCode = $record->code ?? null;
                            $newCode = $data['code'] ?? ('PROD-' . uniqid());
                        
                            // Ruta fija donde guardamos las imágenes
                            $storagePath = 'products/barcodes';
                        
                            // Si el code cambió
                            if ($oldCode !== $newCode) {
                                // 1️⃣ Eliminar la imagen anterior si existe
                                if ($record->barcode && Storage::disk('public')->exists($record->barcode)) {
                                    Storage::disk('public')->delete($record->barcode);
                                }
                        
                                // 2️⃣ Generar la nueva imagen del código de barras
                                $generator = new BarcodeGeneratorPNG();
                                $barcodeImage = $generator->getBarcode($newCode, $generator::TYPE_CODE_128);
                        
                                // 3️⃣ Guardar nueva imagen con el nuevo code
                                $filename = "{$newCode}.png";
                                $fullPath = "{$storagePath}/{$filename}";
                                Storage::disk('public')->put($fullPath, $barcodeImage);
                        
                                // 4️⃣ Actualizar datos para guardar en DB
                                $data['code'] = $newCode;
                                $data['barcode'] = $fullPath;
                            } else {
                                // Si el code no cambia, mantener la ruta de la imagen
                                $data['barcode'] = $record->barcode;
                            }
                        
                            return $data;
                        })
                    ,
                    Tables\Actions\DeleteAction::make()
                        ->before(function (Item $record) {
                            // dd($record);
                            $record->update(['is_available' => false]);
                        }),
                    Tables\Actions\RestoreAction::make()
                        ->after(function (Item $record) {
                            $record->update(['is_available' => true]);
                        })
                ])
                ->button()
                ->label('Acciones')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => 
                $query->latest() // Equivale a ->orderBy('created_at', 'desc')
            )
            ->deferLoading();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
