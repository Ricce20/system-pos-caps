<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashRegisterResource\Pages;
use App\Filament\Resources\CashRegisterResource\RelationManagers;
use App\Models\CashRegister;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use function Laravel\Prompts\search;

class CashRegisterResource extends Resource
{
    protected static ?string $model = CashRegister::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Caja';
    
    protected static ?string $navigationLabel = 'Cajas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de caja')
                    ->placeholder('Nombre de la caja ej:Caja n1')
                    ->required()
                    ->unique(ignoreRecord:true)
                    ->maxLength(255),

                Forms\Components\Toggle::make('is_available')
                    ->label('Activo')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle')
                    ->required()
                    ->onColor('success')
                    ->offColor('danger')
                    ->helperText('Indica si la caja esta disponible para su uso'),

                Forms\Components\Select::make('location_id')
                    ->relationship('location','name', function ($query) {
                        return $query->where('active', true)->whereNull('deleted_at');
                    })
                    ->label('Sucursal Asignado')
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->loadingMessage('Cargando...')
                    ->optionsLimit(20),

                Forms\Components\Select::make('user_id')
                    ->label('Usuario asignado')
                    ->required()
                    ->relationship('user','name', function ($query) {
                        return $query->where('active', true)->whereNull('deleted_at');
                    })
                    ->loadingMessage('Cargando...')
                    ->optionsLimit(20)
                    ->preload()
                    ->searchable()
                    ->native(false),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Cajas')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre de caja')
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('is_available')
                    ->label('Activo')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle'),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Sucursal Asignado')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario Encargado')
                    ->sortable()
                    ->searchable()

                    ->numeric()
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
                    Tables\Actions\DeleteAction::make()
                        ->before(function (CashRegister $record) {
                            // dd($record);
                            $record->update(['is_available' => false]);
                        }),
                    Tables\Actions\RestoreAction::make()
                        ->after(function (CashRegister $record) {
                            $record->update(['is_available' => true]);
                        }),
                    Tables\Actions\Action::make('Ir a Caja')
                        ->label('Ir a caja')
                        ->url(fn (CashRegister $record): string => route('filament.admin.resources.cash-registers.cash', ['record' => $record]))
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
            RelationManagers\CashRegisterDetailRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashRegisters::route('/'),
            'create' => Pages\CreateCashRegister::route('/create'),
            'view' => Pages\ViewCashRegister::route('/{record}'),
            'edit' => Pages\EditCashRegister::route('/{record}/edit'),
            'cash' => Pages\CashRegisterPage::route('/{record}/cash'),
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
