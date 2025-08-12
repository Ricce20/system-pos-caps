<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Establecimiento';
    
    protected static ?string $navigationLabel = 'Establecimientos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->placeholder('Nombre del establecimiento nuevo')
                ->unique(ignoreRecord:true)
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('address')
                ->placeholder('Direccion del establecimeinto')
                ->label('Dirección')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone')
                ->placeholder('3378561276')
                ->label('Teléfono')
                ->unique(ignoreRecord:true)
                ->nullable()
                ->maxLength(10)
                ->placeholder('Telefono de la sucursal(opcional)'),
            Forms\Components\Select::make('warehouse_id')
                ->label('Almacen a utilizar')
                ->native(false)
                ->relationship('warehouse','name', function ($query) {
                    return $query->where('active', true)->whereNull('deleted_at');
                })
                ->searchable()
                ->preload()
                ->loadingMessage('Cargando...')
                ->optionsLimit(20)
                ->required()
                ->helperText('Almacen destinado para las ventas')
                ->unique(),
            Forms\Components\Toggle::make('active')
                ->label('Activo')
                ->onColor('success')
                ->offColor('danger')
                ->onIcon('heroicon-m-check-circle')
                ->offIcon('heroicon-m-x-circle')
                ->helperText('Indica si el establecimiento esta operativo')
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Establecimientos')
            ->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nombre')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('address')
                ->label('Dirección')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('phone')
                ->label('Teléfono')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('warehouse.name')
                ->label('Almacen asignado')
                ->searchable()
                ->sortable(),
            Tables\Columns\ToggleColumn::make('active')
                ->label('Activo')
                ->onColor('success')
                ->offColor('danger')
                ->onIcon('heroicon-m-check-circle')
                ->offIcon('heroicon-m-x-circle')
                ->sortable(),
            Tables\Columns\TextColumn::make('deleted_at')
                ->label('Eliminado')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Creado')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->label('Actualizado')
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
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    // Tables\Actions\DeleteAction::make()
                    //     ->before(function (Location $record) {
                    //         // dd($record);
                    //         $record->update(['active' => false]);
                    //     }),
                    // Tables\Actions\RestoreAction::make()
                    //     ->after(function (Location $record) {
                    //         $record->update(['active' => true]);
                    //     })
                ])
                ->button()
                ->label('Acciones')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    // Tables\Actions\RestoreBulkAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'view' => Pages\ViewLocation::route('/{record}'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
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
