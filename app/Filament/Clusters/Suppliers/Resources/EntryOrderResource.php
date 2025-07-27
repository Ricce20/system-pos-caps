<?php

namespace App\Filament\Clusters\Suppliers\Resources;

use App\Filament\Clusters\Suppliers;
use App\Filament\Clusters\Suppliers\Resources\EntryOrderResource\Pages;
use App\Models\EntryOrder;
use App\Models\Supplier;
use App\Models\Item;
use App\Models\SupplierItem;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\Suppliers\Resources\EntryOrderResource\RelationManagers\EntryOrderDetailRelationManager;
use App\Filament\Clusters\Suppliers\Resources\EntryOrderResource\RelationManagers;

class EntryOrderResource extends Resource
{
    protected static ?string $model = EntryOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Suppliers::class;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            // Campos de la orden de compra
            // Forms\Components\DateTimePicker::make('fecha_orden')
            //     ->label('Fecha de Orden')
            //     ->required()
            //     ->default(Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s'))
            //     ->displayFormat('Y-m-d h:i A') // Formato de 12 horas con AM/PM
            //     ->readOnly(),

            Forms\Components\Select::make('supplier_id')
                ->label('Proveedor')
                ->placeholder('Selecciona a un proveedor')
                ->relationship('supplier','name')
                ->required()
                ->searchable()
                ->native(false)
                // ->options(Supplier::pluck('name', 'id'))
                ->preload()
                ->reactive() // necesario para usarlo dentro del Repeater
                ->afterStateUpdated(function ($state, callable $set) {
                    if (empty($state)) {
                        $set('items', []); // Limpia el repeater si se deselecciona el proveedor
                    }
                })
                ->helperText('Cambiar el proveedor limpiará los productos seleccionados.'),

            Forms\Components\TextInput::make('notes')
                ->label('Notas')
                ->placeholder('Agregar nota importante')
                ->nullable()
                ->maxLength(255),

            // Campo total fuera del repeater
            Forms\Components\TextInput::make('total')
                ->label('Total a pagar')
                ->numeric()
                ->readOnly()
                ->dehydrated(true)
                ->reactive(),

            // Repeater de items/productos
            Forms\Components\Repeater::make('items')
                ->schema([
                    Forms\Components\Select::make('item_id')
                        ->label('Producto')
                        ->options(function (callable $get) {
                            $supplierId = $get('../../supplier_id');
                            if (!$supplierId) {
                                return [];
                            }
                            // Traer los IDs de los items relacionados con el proveedor
                            $itemIds = SupplierItem::where('supplier_id', $supplierId)->where('is_primary',true)->pluck('item_id');
                            // Traer los items con sus relaciones
                            return Item::with(['product', 'size'])
                                ->whereIn('id', $itemIds)
                                ->get()
                                ->mapWithKeys(function ($item) {
                                    $label = "{$item->product->name} - {$item->size->name}";
                                    return [$item->id => $label];
                                });
                        })
                        ->preload()
                        ->native(false)
                        ->searchable()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            // Obtener supplier_id desde el form principal
                            $supplierId = data_get($get('../../supplier_id'), null);

                            if ($supplierId && $state) {
                                $precio = SupplierItem::where('supplier_id', $supplierId)
                                    ->where('item_id', $state)
                                    ->value('purchase_price');

                                $set('precio_compra', $precio ?? 0);
                            } else {
                                $set('precio_compra', 0);
                            }

                            // Calcular subtotal
                            $precio = floatval($get('precio_compra')) ?? 0;
                            $cantidad = floatval($get('quantity')) ?? 0;
                            $set('subtotal', $precio * $cantidad);

                            // Calcular el total general sumando todos los subtotales
                            $members = $get('../../items') ?? [];
                            $total = collect($members)->sum(function ($item) {
                                return floatval($item['subtotal'] ?? 0);
                            });
                            $set('../../total', $total);
                        }),

                    Forms\Components\TextInput::make('quantity')
                        ->label('Cantidad')
                        ->required()
                        ->numeric()
                        ->default(1)
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, callable $get) {
                            $precio = floatval($get('precio_compra')) ?? 0;
                            $cantidad = floatval($get('quantity')) ?? 0;
                            $set('subtotal', $precio * $cantidad);

                            // Calcular el total general sumando todos los subtotales
                            $members = $get('../../items') ?? [];
                            $total = collect($members)->sum(function ($item) {
                                return floatval($item['subtotal'] ?? 0);
                            });
                            $set('../../total', $total);
                        }),
                    Forms\Components\TextInput::make('precio_compra')
                        ->label('Precio de compra')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('subtotal')
                        ->label('SubTotal')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(true),
                ])
                ->hiddenOn(['view','edit'])
                ->columns(4)
                ->reactive()
                ->disabled(fn (callable $get) => empty($get('supplier_id'))),
        ])
        ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Proovedor'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Ralizado por'),
                Tables\Columns\TextColumn::make('date_order')
                    ->dateTime()
                    ->label('Fecha de compra'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notas'),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->prefix('$')
                    ->label('Total de compra')
                    ->alignCenter(),
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
            RelationManagers\EntryOrderDetailRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntryOrders::route('/'),
            'create' => Pages\CreateEntryOrder::route('/create'),
            'view' => Pages\ViewEntryOrder::route('/{record}'),
            'edit' => Pages\EditEntryOrder::route('/{record}/edit'),
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
