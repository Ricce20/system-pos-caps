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
use Filament\Forms\Get;
use Filament\Forms\Set;

class EntryOrderResource extends Resource
{
    protected static ?string $model = EntryOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Suppliers::class;

    protected static ?string $modelLabel = 'Entrada Compra';
    
    protected static ?string $navigationLabel = 'Entradas de Compra';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                // 🔹 Selección de proveedor
                Forms\Components\Select::make('supplier_id')
                    ->label('Proveedor')
                    ->placeholder('Selecciona un proveedor')
                    ->relationship('supplier','name', fn ($query) =>
                        $query->where('is_available', true)->whereNull('deleted_at')
                    )
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->reactive()
                    ->loadingMessage('Cargando...')
                    ->optionsLimit(20)
                    ->afterStateUpdated(function ($state, callable $set) {
                        if(empty($state)) {
                            $set('items', []); // Limpia el repeater si se deselecciona el proveedor
                        }
                    })
                    ->helperText('Cambiar el proveedor limpia los productos seleccionados.'),

                // 🔹 Notas
                Forms\Components\TextInput::make('notes')
                    ->label('Notas')
                    ->placeholder('Agregar nota importante')
                    ->maxLength(255)
                    ->nullable(),

                // 🔹 Total general
                Forms\Components\TextInput::make('total')
                    ->label('Total a pagar')
                    ->numeric()
                    ->readOnly()
                    ->prefix('$')
                    ->dehydrated(true)
                    ->reactive(),

                // 🔹 Repeater de items/productos
                Forms\Components\Repeater::make('items')
                    ->schema([
                        // Producto
                        Forms\Components\Select::make('item_id')
                            ->label('Producto')
                            ->options(function (callable $get) {
                                $supplierId = $get('../../supplier_id');
                                if (!$supplierId) return [];

                                // Cachea los IDs de items primary de este supplier
                                $itemIds = SupplierItem::where('supplier_id', $supplierId)
                                    ->where('is_primary', true)
                                    ->pluck('item_id');

                                // Trae los items disponibles y genera labels
                                return Item::with(['product', 'size'])
                                    ->whereIn('id', $itemIds)
                                    ->where('is_available', true)
                                    ->whereNull('deleted_at')
                                    ->get()
                                    ->mapWithKeys(fn ($item) =>
                                        [$item->id => "{$item->product->name} - {$item->size->name}"]
                                    );
                            })
                            ->preload()
                            ->native(false)
                            ->searchable()
                            ->loadingMessage('Cargando...')
                            ->optionsLimit(20)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $supplierId = $get('../../supplier_id');
                                if ($supplierId && $state) {
                                    // Traer el precio de compra
                                    $precio = SupplierItem::where('supplier_id', $supplierId)
                                        ->where('item_id', $state)
                                        ->value('purchase_price') ?? 0;
                                    $set('precio_compra', $precio);
                                } else {
                                    $set('precio_compra', 0);
                                }

                                // Recalcular subtotal y total
                                // self::recalcularTotales($set, $get);
                            }),

                        // Cantidad
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->live(debounce: 1000)
                            ->afterStateUpdated(function (?string $state, ?string $old,callable $set, callable $get) {
                                if($state != $old){
                                    self::recalcularTotales($set, $get,$state);
                                }
                                return;
                                
                            }),

                        // Precio de compra (solo lectura)
                        Forms\Components\TextInput::make('precio_compra')
                            ->label('Precio de compra')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        // Subtotal (solo lectura)
                        Forms\Components\TextInput::make('subtotal')
                            ->label('SubTotal')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(true),
                    ])
                    ->columns(4)
                    ->reactive()
                    ->hiddenOn(['view','edit'])
                    ->disabled(fn (callable $get) => empty($get('supplier_id'))),
            ])
            ->columns(1);
    }

        /**
     * Helper para recalcular subtotal y total general de la venta.
     */
    protected static function recalcularTotales(callable $set, callable $get, $state): void
    {
        $precio = floatval($get('precio_compra')) ?: 0;
        $cantidad = floatval($state) ?: 0;

        // Subtotal por fila
        $set('subtotal', $precio * $cantidad);

        // Total general sumando todos los subtotales del repeater
        $total = collect($get('../../items') ?? [])
            ->sum(fn ($item) => floatval($item['subtotal'] ?? 0));
        $set('../../total', $total);
        return;
    }



    public static function table(Table $table): Table
    {
        return $table
            ->heading('Registro de Compras')
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable()
                    ->label('Proovedor'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Ralizado por'),
                Tables\Columns\TextColumn::make('date_order')
                    ->dateTime()
                    ->label('Fecha de compra'),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->prefix('$')
                    ->label('Total de compra')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                ])
                ->button()
                ->label('Acciones')
            ])
            ->modifyQueryUsing(fn (Builder $query) => 
                $query->latest() // Equivale a ->orderBy('created_at', 'desc')
            )
            ->deferLoading();
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\DeleteBulkAction::make(),
            //         Tables\Actions\ForceDeleteBulkAction::make(),
            //         Tables\Actions\RestoreBulkAction::make(),
            //     ]),
            // ]);
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
