<?php

namespace App\Filament\Clusters\Products\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class ItemRelationManager extends RelationManager
{
    protected static string $relationship = 'Item';

    protected static ?string $title = 'Producto Tallas';


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
                Forms\Components\TextInput::make('barcode')
                    ->label('Código de barra')
                    ->placeholder('Ingrese el código de barra')
                    ->required()
                    ->unique(ignoreRecord:true),
                // Forms\Components\TextInput::make('price')
                //     ->label('Precio')
                //     ->prefix('$')
                //     ->required()
                //     ->numeric()
                //     ->minValue(function (callable $get) {
                //         // Obtener el precio máximo de compra entre todos los proveedores
                //         $maxPurchasePrice = DB::table('supplier_items')
                //             ->where('item_id', $get('id'))
                //             ->max('purchase_price');
                        
                //         return $maxPurchasePrice ?? 0;
                //     })
                //     ->helperText(function (callable $get) {
                //         $maxPurchasePrice = DB::table('supplier_items')
                //             ->where('item_id', $get('id'))
                //             ->max('purchase_price');
                        
                //         return $maxPurchasePrice 
                //             ? "El precio no puede ser menor al precio de compra: $".number_format($maxPurchasePrice, 2)
                //             : "Primero asigna proveedores y precios de compra";
                //     }),
                Forms\Components\Toggle::make('is_available')
                    ->label('Disponible')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Producto por Tallas')
            ->recordTitleAttribute('product_id')
            ->columns([
                Tables\Columns\TextColumn::make('size.name')
                    ->label('Talla'),
                Tables\Columns\TextColumn::make('size.measurement')
                    ->label('Medida'),
                // Tables\Columns\TextColumn::make('price')
                //     ->label('Precio')
                //     ->prefix('$'),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Código de barra')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Disponible')
                    ->boolean()
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
                    ->label('Registrar nueva Talla'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
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
