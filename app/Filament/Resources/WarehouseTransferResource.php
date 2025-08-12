<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseTransferResource\Pages;
use App\Filament\Resources\WarehouseTransferResource\RelationManagers;
use App\Models\WarehouseItem;
use App\Models\WarehouseTransfer;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class WarehouseTransferResource extends Resource
{
    protected static ?string $model = WarehouseTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Transferencias de Almacén';
    protected static ?string $label = 'Transferencia de Almacén';
    protected static ?string $pluralLabel = 'Transferencias de Almacén';
    protected static ?string $navigationGroup = 'Inventario';
    public static function form(Form $form): Form
    {
        
        return $form
            ->schema([
                
                    Forms\Components\Select::make('source_warehouse_id')
                        ->label('Almacén Origen')
                        ->relationship('sourceWarehouse', 'name', fn($query) => $query->where('active',true))
                        ->required()
                        ->preload()
                        ->searchable()
                        ->native(false)
                        ->live(onBlur: true)
                        ->helperText('Selecciona el almacén de donde saldrá el producto.'),
                    
                        Forms\Components\Select::make('destination_warehouse_id')
                            ->label('Almacén Destino')
                            ->native(false)
                            ->relationship('destinationWarehouse', 'name',fn($query) => $query->where('active',true))
                            ->required()
                            ->reactive()
                            ->preload()
                            ->searchable()
                            ->helperText('Selecciona el almacén al que llegará el producto.')
                            ->different('source_warehouse_id')
                            ->disabled(fn (Get $get) => empty($get('source_warehouse_id')))
                            ->afterStateUpdated(function(?string $state, Get $get){
                                $sourceWarehouseId = $get('source_warehouse_id');
                                if($state == $sourceWarehouseId){
                                     Notification::make()
                                    ->title('El almacén de destino no puede ser el mismo que el de origen.')
                                    ->danger()
                                    ->send();
                                     // $get('destination_warehouse_id', null);
                                    }
                                }),

                    // NOTAS
                    Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->nullable(),


                    // REPEATER DE PRODUCTOS
                    Forms\Components\Repeater::make('items')
                        ->label('Productos a transferir')
                        ->schema([
                            Forms\Components\Select::make('item_id')
                            ->label('Producto')
                            ->options(function (callable $get) {
                                $sourceWarehouseId = $get('../../source_warehouse_id');
                                if (!$sourceWarehouseId) return [];
                                    // Traer los items con stock en el almacén origen
                                    $items = \App\Models\WarehouseItem::where('warehouse_id', $sourceWarehouseId)
                                            ->where('stock', '>', 0)
                                            ->with(['item.product', 'item.size'])
                                            ->get();
                                    return $items->mapWithKeys(function ($wi) {
                                        $nombre = $wi->item->product->name ?? '';
                                        $talla = $wi->item->size->name ?? '';
                                        $stock = $wi->stock ?? 0;
                                        return [
                                            $wi->item_id => "{$nombre} - {$talla} (Stock: {$stock})"
                                        ];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->live(onBlur: true)
                            ->disabled(fn (callable $get) => empty($get('../../source_warehouse_id')))
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $sourceWarehouseId = $get('../../source_warehouse_id');
                                    if ($state && $sourceWarehouseId) {
                                        $stock = \App\Models\WarehouseItem::where('warehouse_id', $sourceWarehouseId)
                                                ->where('item_id', $state)
                                                ->value('stock') ?? 0;
                                        $set('max_quantity', $stock);
                                        } else {
                                            $set('max_quantity', 0);
                                        }
                            }),

                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(fn (callable $get) => $get('max_quantity') ?? 1)
                                ->helperText(fn (callable $get) => 'Stock disponible: ' . ($get('max_quantity') ?? 0)),
                            Forms\Components\Hidden::make('max_quantity'),
                            ])
                            ->visibleOn('create')
                            ->columns(2)
                            ->reactive()
                            ->disabled(fn (callable $get) => empty($get('source_warehouse_id'))),

                    

                    // NÚMERO DE TRANSFERENCIA
                    Forms\Components\TextInput::make('transfer_number')
                        ->label('Número de transferencia')
                        ->disabled()
                        ->visibleOn('view'),

                    // ESTADO
                    Forms\Components\TextInput::make('status')
                        ->label('Estado')
                        ->disabled()
                        ->visibleOn('view'),

                    // CREADO POR
                    Forms\Components\Select::make('created_by')
                        ->relationship('user','name')
                        ->label('Creado por')
                        ->disabled()
                        ->visibleOn('view'),
                    
                     // Aprovado POR
                    Forms\Components\Select::make('approved_by')
                    ->relationship('user','name')
                    ->label('Aprobado por')
                    ->disabled()
                    ->visibleOn('view'),

                    // APROBADO EL
                    Forms\Components\DateTimePicker::make('approved_at')
                        ->label('Aprobado el')
                        ->disabled()
                        ->visibleOn('view'),

                    // COMPLETADO EL
                    Forms\Components\DateTimePicker::make('completed_at')
                        ->label('Completado el')
                        ->disabled()
                        ->visibleOn('view'),

                    // CREADO EL
                    Forms\Components\DateTimePicker::make('created_at')
                        ->label('Creado el')
                        ->disabled()
                        ->visibleOn('view'),

                    // ACTUALIZADO EL
                    Forms\Components\DateTimePicker::make('updated_at')
                        ->label('Actualizado el')
                        ->disabled()
                        ->visibleOn('view'),

            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')
                    ->label('Número de transferencia')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sourceWarehouse.name')
                    ->label('Origen')
                    ->sortable(),
                Tables\Columns\TextColumn::make('destinationWarehouse.name')
                    ->label('Destino')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(function ($state) {
                        // Devuelve el texto en español y un emoji según el estado
                        switch ($state) {
                            case 'pending':
                                return '⏳ Pendiente';
                            case 'completed':
                                return '✅ Completada';
                            case 'cancelled':
                                return '❌ Cancelada';
                            default:
                                return ucfirst($state);
                        }
                    })
                    ->color(function ($state) {
                        // Asigna color según el estado
                        switch ($state) {
                            case 'pending':
                                return 'warning';
                            case 'completed':
                                return 'success';
                            case 'cancelled':
                                return 'danger';
                            default:
                                return 'gray';
                        }
                    })
                    ->label('Estado')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Creado por')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime()
                    ->label('Aprobado el')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->label('Completado el')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
            Tables\Actions\Action::make('generar_reporte_pdf')
                ->label('Generar Reporte PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('fecha_inicio')
                        ->label('Fecha de inicio')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('fecha_fin')
                        ->label('Fecha de fin')
                        ->required()
                        ->after('fecha_inicio'),
                ])
                ->action(function (array $data) {
                    $fechaInicio = $data['fecha_inicio'];
                    $fechaFin = $data['fecha_fin'];

                    // Traer las transferencias en el rango de fechas
                    $transferencias = \App\Models\WarehouseTransfer::with([
                        'warehouseTransferDetail.item.product',
                        'warehouseTransferDetail.item.size',
                        'sourceWarehouse',
                        'destinationWarehouse',
                        'user'
                    ])
                    ->whereDate('created_at', '>=', $fechaInicio)
                    ->whereDate('created_at', '<=', $fechaFin)
                    ->get();

                    if ($transferencias->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se encontraron transferencias en el rango de fechas seleccionado.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Generar el PDF usando DomPDF
                    $pdf = Pdf::loadView('pdf-transfer-report-multiple', [
                        'transferencias' => $transferencias,
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin,
                    ]);

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'reporte_transferencias_' . $fechaInicio . '_a_' . $fechaFin . '.pdf'
                    );
                }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('completar')
                    ->label('Completar')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === \App\Models\WarehouseTransfer::STATUS_PENDING)
                    ->action(function ($record) {
                        // Cambiar el estado a completado
                        $record->status = \App\Models\WarehouseTransfer::STATUS_COMPLETED;
                        $record->completed_at = now();


                        // Ajustar el stock en los almacenes
                        foreach ($record->warehouseTransferDetail as $detalle) {
                            // Disminuir stock en el almacén origen
                            $itemOrigen = \App\Models\WarehouseItem::where('warehouse_id', $record->source_warehouse_id)
                                ->where('item_id', $detalle->item_id)
                                ->first();

                            if ($itemOrigen) {
                                $itemOrigen->stock -= $detalle->quantity;
                                if ($itemOrigen->stock < 0) {
                                    $itemOrigen->stock = 0;
                                }
                                $itemOrigen->save();
                            }

                            // Aumentar stock en el almacén destino
                            $itemDestino = \App\Models\WarehouseItem::firstOrCreate(
                                [
                                    'warehouse_id' => $record->destination_warehouse_id,
                                    'item_id' => $detalle->item_id,
                                    'is_available' => true,
                                ],
                                [
                                    'stock' => 0,
                                ]
                            );
                            $itemDestino->stock += $detalle->quantity;
                            $itemDestino->save();
                        }

                        $record->save();

                        \Filament\Notifications\Notification::make()
                        ->title('Transferencia realizada correctamente')
                        ->success()
                        ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Completar transferencia')
                    ->modalDescription('¿Está seguro que desea completar esta transferencia? Esto ajustará el stock en los almacenes.'),

                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === \App\Models\WarehouseTransfer::STATUS_PENDING)
                    ->action(function ($record) {
                        // Cambiar el estado a cancelado, sin mover stock
                        $record->status = \App\Models\WarehouseTransfer::STATUS_CANCELLED;
                        $record->completed_at = now();
                        $record->save();

                        \Filament\Notifications\Notification::make()
                        ->title('Transferencia cancelada, sin movimientos.')
                        ->success()
                        ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar transferencia')
                    ->modalDescription('¿Está seguro que desea cancelar esta transferencia? No se realizarán movimientos de stock.'),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    // Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WarehouseTransferDetailRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouseTransfers::route('/'),
            'create' => Pages\CreateWarehouseTransfer::route('/create'),
            'view' => Pages\ViewWarehouseTransfer::route('/{record}'),
            'edit' => Pages\EditWarehouseTransfer::route('/{record}/edit'),
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
