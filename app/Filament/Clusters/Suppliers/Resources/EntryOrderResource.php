<?php

namespace App\Filament\Clusters\Suppliers\Resources;

use App\Filament\Clusters\Suppliers;
use App\Filament\Clusters\Suppliers\Resources\EntryOrderResource\Pages;
use App\Models\EntryOrder;
use App\Models\Item;
use App\Models\SupplierItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\Suppliers\Resources\EntryOrderResource\RelationManagers;
use Illuminate\Support\Facades\Blade;
use Barryvdh\DomPDF\Facade\Pdf;

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
                    ->placeholder('Selecciona un proveedor.')
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
                    ->placeholder('Agregar nota importante.')
                    ->maxLength(255)
                    ->nullable(),

                // 🔹 Total general
                Forms\Components\TextInput::make('total')
                    ->label('Total a pagar')
                    ->numeric()
                    ->readOnly()
                    ->prefix('$')
                    ->suffix('MXN')
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
                            ->suffix('MXN')
                            ->disabled()
                            ->dehydrated(false),

                        // Subtotal (solo lectura)
                        Forms\Components\TextInput::make('subtotal')
                            ->label('SubTotal')
                            ->numeric()
                            ->prefix('$')
                            ->suffix('MXN')
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
            ->headerActions([
                Tables\Actions\Action::make('generate_pdf_report')
                    ->label('Generar Reporte PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha de Inicio')
                            ->required()
                            ->placeholder('Selecciona la fecha de inicio'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha de Fin')
                            ->required()
                            ->placeholder('Selecciona la fecha de fin')
                            ->after('start_date'),
                    ])
                    ->action(function (array $data) {
                        $startDate = $data['start_date'];
                        $endDate = $data['end_date'];

                        // Obtener las entradas de compra en el rango de fechas
                        $records = EntryOrder::with([
                            'supplier.brand',
                            'user'
                        ])
                        ->whereBetween('date_order', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->orderBy('date_order', 'desc')
                        ->get();

                        // Calcular estadísticas
                        $totalOrders = $records->count();
                        $totalAmount = $records->sum('total');
                        $uniqueSuppliers = $records->unique('supplier_id')->count();

                        // Resumen por proveedor
                        $supplierSummary = $records->groupBy('supplier_id')
                            ->map(function ($orders, $supplierId) {
                                $supplier = $orders->first()->supplier;
                                return (object) [
                                    'supplier_name' => $supplier->name,
                                    'order_count' => $orders->count(),
                                    'total_amount' => $orders->sum('total')
                                ];
                            })
                            ->values();

                        // Generar fecha local
                        $fecha = now()->format('d/m/Y H:i:s');

                        // Generar nombre del archivo
                        $fileName = 'reporte_entradas_compra_' . $startDate . '_' . $endDate . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';

                        return response()->streamDownload(function () use ($records, $totalOrders, $totalAmount, $uniqueSuppliers, $supplierSummary, $fecha, $startDate, $endDate) {
                            echo Pdf::loadHtml(
                                Blade::render('pdf-entry-orders', [
                                    'records' => $records,
                                    'totalOrders' => $totalOrders,
                                    'totalAmount' => $totalAmount,
                                    'uniqueSuppliers' => $uniqueSuppliers,
                                    'supplierSummary' => $supplierSummary,
                                    'fecha' => $fecha,
                                    'minDate' => $startDate,
                                    'maxDate' => $endDate
                                ])
                            )->stream();
                        }, $fileName, [
                            'Content-Type' => 'application/pdf',
                        ]);
                    })
                    ->modalHeading('Generar Reporte PDF de Entradas de Compra')
                    ->modalDescription('Selecciona el rango de fechas para generar el reporte')
                    ->modalSubmitActionLabel('Generar PDF')
                    ->modalCancelActionLabel('Cancelar'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Folio')
                    ->numeric()
                    ->searchable()
                    ->prefix('Folio-'),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable()
                    ->label('Proovedor'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Realizado por'),
                Tables\Columns\TextColumn::make('date_order')
                    ->dateTime()
                    ->label('Fecha de compra'),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->prefix('$')
                    ->suffix('MXN')
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
                    Tables\Actions\Action::make('generate_entry_order_pdf')
                        ->label('Generar PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (EntryOrder $record) {
                            // Cargar la entrada de compra con sus relaciones
                            $entryOrder = EntryOrder::with([
                                'supplier.brand',
                                'user',
                                'entryOrderDetail.item.product.brand',
                                'entryOrderDetail.item.product.category',
                                'entryOrderDetail.item.product.modelCap',
                                'entryOrderDetail.item.size'
                            ])
                            ->find($record->id);

                            // Calcular estadísticas
                            $totalItems = $entryOrder->entryOrderDetail->count();
                            $totalQuantity = $entryOrder->entryOrderDetail->sum('quantity');
                            $totalWaste = $entryOrder->entryOrderDetail->sum('amount_of_waste');

                            // Generar fecha local
                            $fecha = now()->format('d/m/Y H:i:s');

                            // Generar nombre del archivo
                            $fileName = 'entrada_compra_folio_' . $entryOrder->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';

                            return response()->streamDownload(function () use ($entryOrder, $totalItems, $totalQuantity, $totalWaste, $fecha) {
                                echo Pdf::loadHtml(
                                    Blade::render('pdf-entry-order-detail', [
                                        'entryOrder' => $entryOrder,
                                        'totalItems' => $totalItems,
                                        'totalQuantity' => $totalQuantity,
                                        'totalWaste' => $totalWaste,
                                        'fecha' => $fecha
                                    ])
                                )->stream();
                            }, $fileName, [
                                'Content-Type' => 'application/pdf',
                            ]);
                        })
                        ->modalHeading('Generar PDF de Entrada de Compra')
                        ->modalDescription('Se generará un reporte PDF detallado de esta entrada de compra.')
                        ->modalSubmitActionLabel('Generar PDF')
                        ->modalCancelActionLabel('Cancelar'),
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
