<?php

namespace App\Filament\Resources\WarehouseTransferResource\RelationManagers;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WarehouseTransferDetailRelationManager extends RelationManager
{
    protected static string $relationship = 'WarehouseTransferDetail';

    protected static ?string $title = 'Detalles de la Transferencia de Almacén';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('item_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item_id')
            ->columns([
                Tables\Columns\TextColumn::make('item.product.id')
                    ->label('ID del producto'),
                Tables\Columns\TextColumn::make('item.product.name')
                    ->label('Producto')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('item.size.name')
                    ->label('Talla')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('imprimir_reporte_pdf')
                    ->label('Imprimir reporte PDF')
                    ->icon('heroicon-o-printer')
                    ->action(function ($livewire) {
                        $transferencia = $livewire->getOwnerRecord();
                        $detalles = $transferencia->warehouseTransferDetail()->with(['item.product', 'item.size'])->get();

                        $pdf = Pdf::loadView('pdf-transfer-report', [
                            'transferencia' => $transferencia,
                            'detalles' => $detalles,
                        ]);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'transferencia_' . $transferencia->id . '.pdf'
                        );
                    })
                    ->color('primary'),
                ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
