<?php

namespace App\Filament\Clusters\Suppliers\Resources\SupplierResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class SupplierItemRelationManager extends RelationManager
{
    protected static string $relationship = 'supplierItems';

    protected static ?string $title = 'Productos del Proveedor';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->label('Producto')
                    ->required()
                    ->relationship('item', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                        $record->product->name . ' - ' . $record->size->name
                    )
                    ->searchable()
                    ->preload()
                    ->unique(modifyRuleUsing: function (Unique $rule,Get $get) {
                        return $rule->where('supplier_id', $this->getOwnerRecord()->getKey())->where('item_id',$get('item_id'));
                    },ignoreRecord: true),
                Forms\Components\TextInput::make('purchase_price')
                    ->label('Precio de Compra')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0)
                    ->afterStateUpdated(function ($state, Set $set) {
                        $set('sale_price', $state); // <- Aquí se copia el valor al precio de venta
                    })
                    ->live(onBlur: true),
                Forms\Components\TextInput::make('sale_price')
                    ->label('Precio de Venta')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->minValue(fn (Get $get) =>floatval($get('purchase_price')) ?: 0)
                    ->helperText('El precio de venta no puede ser menor al precio de compra')
                    ->dehydrated(),
                Forms\Components\Toggle::make('is_available')
                    ->label('Disponible')
                    ->required()
                    ->default(true),
                Forms\Components\Toggle::make('is_primary')
                    ->label('Proveedor principal de este articulo producto?')
                    ->required()
                    ->default(true),
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
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Proovedor principal')
                    ->boolean()
                    ->sortable(),
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
                    ->label('Agregar Producto'),

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
