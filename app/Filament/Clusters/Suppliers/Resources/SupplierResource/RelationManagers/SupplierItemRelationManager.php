<?php

namespace App\Filament\Clusters\Suppliers\Resources\SupplierResource\RelationManagers;

use App\Models\SupplierItem;
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

use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class SupplierItemRelationManager extends RelationManager
{
    protected static string $relationship = 'supplierItems';

    protected static ?string $title = 'Productos del Proveedor';

    protected static ?string $modelLabel = 'Producto';


    public function form(Form $form): Form
    {
        return $form
        ->schema([
    
            // 🔹 Selección de producto
            Forms\Components\Select::make('item_id')
                ->label('Producto')
                ->required()
                ->relationship('item', 'id', fn(Builder $query)=> $query->where('is_available',true)->WhereNull('deleted_at'))
                ->getOptionLabelFromRecordUsing(fn ($record) => 
                    $record->product->name . ' - ' . $record->size->name
                )
                ->searchable()
                ->preload()
                ->native(false)
                ->loadingMessage('Cargando...')
                ->optionsLimit(20)
                ->unique(
                    modifyRuleUsing: function (Unique $rule, Get $get) {
                        // Valida que no exista el mismo item para este supplier
                        return $rule
                            ->where('supplier_id', $this->getOwnerRecord()->getKey());
                    },
                    ignoreRecord: true
                ),
    
            // 🔹 Precio de compra
            Forms\Components\TextInput::make('purchase_price')
                ->label('Precio de Compra')
                ->required()
                ->numeric()
                ->prefix('$')
                ->minValue(0)
                // ->afterStateUpdated(function ($state, Set $set) {
                //     // Copia automáticamente al precio de venta al escribir
                //     $set('sale_price', $state);
                // })
                ->live(onBlur: true),
    
            // 🔹 Precio de venta
            Forms\Components\TextInput::make('sale_price')
                ->label('Precio de Venta')
                ->required()
                ->numeric()
                ->prefix('$')
                ->minValue(fn (Get $get) => floatval($get('purchase_price')) ?: 0)
                ->helperText('El precio de venta no puede ser menor al precio de compra'),
    
            // 🔹 Disponible
            Forms\Components\Toggle::make('is_available')
                ->label('Disponible')
                ->helperText('Seleccione si el producto está disponible con este proveedor')
                ->onColor('success')
                ->offColor('danger')
                ->onIcon('heroicon-m-check-circle')
                ->offIcon('heroicon-m-x-circle')
                ->default(true),
    
            // 🔹 Proveedor principal
            Forms\Components\Toggle::make('is_primary')
                ->label('¿Proveedor principal de este producto?')
                ->helperText('Solo un proveedor puede ser principal por producto.')
                ->onColor('success')
                ->offColor('danger')
                ->onIcon('heroicon-m-check-circle')
                ->offIcon('heroicon-m-x-circle')
                ->live(onBlur:true)
                ->afterStateUpdated(function (?bool $state, ?bool $old, Get $get) {
                    // dd($state);
                    // Solo mostramos si lo activó
                    if ($state) {
                        $itemId = $get('item_id');
            
                        $previousExists = SupplierItem::where('item_id', $itemId)
                            ->where('is_primary', true)
                            ->exists();
            
                        if ($previousExists) {
                            Notification::make()
                                ->warning()
                                ->title('Ya existe un proveedor principal')
                                ->body('Si guardas este registro, se reemplazará al anterior.')
                                ->send();
                        }
                    }
                }),
        ]);
    
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item.product.name')
            ->columns([
                Tables\Columns\TextColumn::make('item.product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.size.name')
                    ->label('Talla')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Precio de Compra')
                    ->prefix('$')
                    ->money('MNX')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Precio de Venta')
                    ->prefix('$')
                    ->money('MNX')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Disponible')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('item.is_available')
                    ->label('Producto Disponible')
                    ->boolean(),              
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Proovedor principal')
                    ->boolean()
                    ->sortable()
                    
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
                    ->default(true)
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make('create')
                    ->label('Agregar Producto')
                    ->before(function (array $data) {
                        $previous = SupplierItem::where('item_id', $data['item_id'])
                            ->where('supplier_id','!=', $this->getOwnerRecord()->id)
                            ->where('is_primary', true)
                            ->first();
            
                        if ($previous && $data['is_primary']) {
                            Notification::make()
                                ->warning()
                                ->title('Proveedor principal Actualizado')
                                ->body('Se ha actualizado el proveedor principal de este producto')
                                ->persistent()
                                ->send(); 

                            $previous->update(['is_primary' => false]);
                        }

                        // Si no había previous, Filament seguirá y guardará normalmente:
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->hidden(fn(Model $record) => !$record->item->is_available)
                        ->before(function (array $data) {
                            $previous = SupplierItem::where('item_id', $data['item_id'])
                                ->where('supplier_id','!=', $this->getOwnerRecord()->id)
                                ->where('is_primary', true)
                                ->first();
                
                            if ($previous && $data['is_primary']) {
                                Notification::make()
                                    ->warning()
                                    ->title('Proveedor principal Actualizado')
                                    ->body('Se ha actualizado el proveedor principal de este producto')
                                    ->persistent()
                                    ->send(); 

                                $previous->update(['is_primary' => false]);
                            }

                            // Si no había previous, Filament seguirá y guardará normalmente:
                        }),
                    // Tables\Actions\DeleteAction::make()
                    //     ->before(function (SupplierItem $record) {
                    //         // dd($record);
                    //         $record->update(['is_available' => false,'is_primary' => false]);
                    //     }),
                    // Tables\Actions\RestoreAction::make()
                    //     ->after(function (SupplierItem $record) {
                    //         $record->update(['is_available' => true]);
                    //     })
                ])
                ->button()
                ->label('Acciones')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
