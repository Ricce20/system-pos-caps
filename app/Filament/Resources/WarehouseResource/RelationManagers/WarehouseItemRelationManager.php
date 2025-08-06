<?php

namespace App\Filament\Resources\WarehouseResource\RelationManagers;

use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class WarehouseItemRelationManager extends RelationManager
{
    protected static string $relationship = 'WarehouseItems';

    protected static ?string $label = 'Nuevo Producto En Almacen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->label('Producto')
                    // ->relationship(
                    //     'item','product_id'
                    // )
                    ->options(function(){
                        $itemsId = WarehouseItem::where('warehouse_id',$this->getOwnerRecord()->id)->pluck('item_id');

                        return Item::with(['product','size'])->whereNotIn('id',$itemsId)->where('is_available',true)->get()
                        ->mapWithKeys(fn ($item) => [
                            $item->id => "{$item->product->name} - {$item->size->name}"
                        ]);;
                    })
                    // ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->product->name} - {$record->size->name}")
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->unique(modifyRuleUsing: function(Unique $rule, Get $get){
                        return $rule->where('warehouse_id',$this->getOwnerRecord()->getKey())->where('item_id',$get('item_id'));
                    }),
                Forms\Components\TextInput::make('stock')
                    ->label('Stock(Cantidad)')
                    ->required()
                    ->numeric()
                    ->placeholder('ej: 100')
                    ->minValue(1),
                Forms\Components\Toggle::make('is_available')
                    ->label('Disponible')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle')
                    ->required()
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Productos en Almacen')
            ->recordTitleAttribute('warehouse_id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->numeric(),
                Tables\Columns\TextColumn::make('item.product.name')
                    ->searchable()
                    ->label('Producto'),
                Tables\Columns\TextColumn::make('item.size.name')
                    ->label('Talla'),
                Tables\Columns\TextColumn::make('stock')
                ->badge()
                ->color(function (int $state): string {
                    if ($state <= 10) {
                        return 'danger';
                    } elseif ($state <= 40) {
                        return 'warning';
                    } else {
                        return 'success';
                    }
                }),
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Estado')
                    ->getStateUsing(function (WarehouseItem $record): string {
                        if ($record->stock <= 10) {
                            return 'Crítico';
                        } elseif ($record->stock <= 40) {
                            return 'Bajo';
                        } else {
                            return 'Normal';
                        }
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'Crítico' => 'danger',
                            'Bajo' => 'warning',
                            default => 'success',
                        };
                    }),
                Tables\Columns\ToggleColumn::make('is_available')
                    ->label('Disponible')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar producto'),
                Tables\Actions\Action::make('print_pdf')
                    ->label('Imprimir Inventrio PDF')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('stock_filter')
                            ->label('Filtro de Stock')
                            ->options([
                                'all' => 'Todos los artículos',
                                'normal' => 'Stock Normal (>40)',
                                'low' => 'Stock Bajo (11-40)',
                                'critical' => 'Stock Crítico (≤10)'
                            ])
                            ->default('all')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $warehouse = $this->getOwnerRecord();
                        
                        // Construir la consulta base
                        $query = WarehouseItem::with([
                            'item.product.brand',
                            'item.product.category',
                            'item.size',
                            'warehouse'
                        ])->where('warehouse_id', $warehouse->id);
                        
                        // Aplicar filtro de stock
                        switch ($data['stock_filter']) {
                            case 'normal':
                                $query->where('stock', '>', 40);
                                $filterName = 'Stock Normal (>40)';
                                break;
                            case 'low':
                                $query->whereBetween('stock', [11, 40]);
                                $filterName = 'Stock Bajo (11-40)';
                                break;
                            case 'critical':
                                $query->where('stock', '<=', 10);
                                $filterName = 'Stock Crítico (≤10)';
                                break;
                            default:
                                $filterName = 'Todos los artículos';
                                break;
                        }
                        
                        $records = $query->get();
                        
                        // Calcular estadísticas
                        $countItems = $records->count();
                        $countNormal = $records->where('stock', '>', 40)->count();
                        $countLow = $records->whereBetween('stock', [11, 40])->count();
                        $countCritical = $records->where('stock', '<=', 10)->count();
                        
                        // Generar PDF
                        $pdf = Pdf::loadView('pdf-warehouse-items', [
                            'records' => $records,
                            'warehouse' => $warehouse,
                            'filterName' => $filterName,
                            'fecha' => now()->format('d/m/Y H:i:s'),
                            'countItems' => $countItems,
                            'countNormal' => $countNormal,
                            'countLow' => $countLow,
                            'countCritical' => $countCritical,
                        ]);
                        
                        // Generar nombre del archivo
                        $fileName = 'reporte_almacen_' . $warehouse->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
                        
                        // Descargar el PDF
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, $fileName, [
                            'Content-Type' => 'application/pdf',
                        ]);
                    })
                    ->modalHeading('Generar Reporte PDF')
                    ->modalDescription('Selecciona el filtro de stock para el reporte')
                    ->modalSubmitActionLabel('Generar PDF')
                    ->modalCancelActionLabel('Cancelar'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->before(function (WarehouseItem $record) {
                            // dd($record);
                            $record->update(['is_available' => false]);
                        }),
                    Tables\Actions\RestoreAction::make()
                        ->after(function (WarehouseItem $record) {
                            $record->update(['is_available' => true]);
                        })
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
            ->deferLoading()->modifyQueryUsing(fn (Builder $query) => 
                $query->latest() // Equivale a ->orderBy('created_at', 'desc')
            )
            ->deferLoading();
    }
}
