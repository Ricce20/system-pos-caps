<?php

namespace App\Filament\Clusters\Products\Resources;

use App\Filament\Clusters\Products;
use App\Filament\Clusters\Products\Resources\ItemResource\Pages;
use App\Filament\Clusters\Products\Resources\ItemResource\RelationManagers;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Products::class;

    protected static ?string $modelLabel = 'Articulo';
    
    protected static ?string $navigationLabel = 'Articulos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->required()
                    ->relationship('product','name')
                    ->preload()
                    ->native(false),
                Forms\Components\Select::make('size_id')
                    ->required()
                    ->relationship('size','name')
                    ->preload()
                    ->native(false),
                Forms\Components\TextInput::make('barcode')
                    ->label('Código de barra')
                    ->placeholder('Ingrese el código de barra')
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->prefix('$')
                    ->required()
                    ->numeric()
                    ->minValue(function (callable $get) {
                        // Obtener el precio máximo de compra entre todos los proveedores
                        $maxPurchasePrice = DB::table('supplier_items')
                            ->where('item_id', $get('id'))
                            ->max('purchase_price');
                        
                        return $maxPurchasePrice ?? 0;
                    })
                    ->helperText(function (callable $get) {
                        $maxPurchasePrice = DB::table('supplier_items')
                            ->where('item_id', $get('id'))
                            ->max('purchase_price');
                        
                        return $maxPurchasePrice 
                            ? "El precio no puede ser menor al precio de compra: $".number_format($maxPurchasePrice, 2)
                            : "Primero asigna proveedores y precios de compra";
                    }),
                Forms\Components\Toggle::make('is_available')
                    ->label('Disponible')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->sortable(),
                Tables\Columns\TextColumn::make('size.name')
                    ->label('Talla')
                    ->sortable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Código de barra')
                    ->sortable(),
                Tables\Columns\TextColumn::make('extra_price')
                    ->label('Precio adicional')
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'view' => Pages\ViewItem::route('/{record}'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
