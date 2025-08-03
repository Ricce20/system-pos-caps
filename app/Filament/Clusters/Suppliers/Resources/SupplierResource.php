<?php

namespace App\Filament\Clusters\Suppliers\Resources;

use App\Filament\Clusters\Suppliers;
use App\Filament\Clusters\Suppliers\Resources\SupplierResource\Pages;
use App\Filament\Clusters\Suppliers\Resources\SupplierResource\RelationManagers;
use App\Filament\Clusters\Suppliers\Resources\SupplierResource\RelationManagers\SupplierItemRelationManager;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Suppliers::class;

    protected static ?string $modelLabel = 'Proveedor';
    
    protected static ?string $pluralModelLabel = 'Proveedores';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->unique(ignoreRecord:true)
                    ->required()
                    ->placeholder('Ingrese el nombre del proveedor')
                    ->maxLength(50),
                Forms\Components\TextInput::make('address')
                    ->placeholder('Ingrese la dirección del proveedor')
                    ->label('Dirección')
                    ->maxLength(255)
                    ->nullable(),
                Forms\Components\TextInput::make('phone')
                    ->label('Teléfono')
                    ->placeholder('Ingrese el teléfono del proveedor')
                    ->tel()
                    ->required()
                    ->unique(ignoreRecord:true)
                    ->maxLength(10),
                Forms\Components\Select::make('brand_id')
                    ->placeholder('Seleccione la marca del proveedor')
                    ->label('Marca')
                    ->required()
                    ->native('false')
                    ->relationship('brand','name', function ($query) {
                        return $query->where('is_available', true)->whereNull('deleted_at');
                    })
                    ->searchable()
                    ->preload()
                    ->loadingMessage('Cargando...')
                    ->optionsLimit(20),
                Forms\Components\Toggle::make('is_available')
                    ->helperText('Seleccione si el proveedor está disponible')
                    ->label('Disponible')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle')
                    ->required(),
            ]);
    }

    /**
     * Helper para recalcular subtotal y total general
     */
    protected static function recalcularTotales(callable $set, callable $get): void
    {
        $precio = floatval($get('precio_compra')) ?: 0;
        $cantidad = floatval($get('quantity')) ?: 0;

        // Subtotal por fila
        $set('subtotal', $precio * $cantidad);

        // Total general sumando todos los subtotales del repeater
        $total = collect($get('../../items') ?? [])
            ->sum(fn ($item) => floatval($item['subtotal'] ?? 0));
        $set('../../total', $total);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Proveedores')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->alignCenter()
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_available')
                    ->label('Disponible')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle'),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Eliminado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->before(function (Supplier $record) {
                            // dd($record);
                            $record->update(['is_available' => false]);
                        }),
                    Tables\Actions\RestoreAction::make()
                        ->after(function (Supplier $record) {
                            $record->update(['is_available' => true]);
                        })
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\SupplierItemRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
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
