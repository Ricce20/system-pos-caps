<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Empleado';
    
    protected static ?string $navigationLabel = 'Mis Empleados';

    protected static ?string $navigationGroup = 'Personal';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->placeholder('Nombre del Empleado')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('paternal_last_name')
                    ->label('Apellido Paterno')
                    ->placeholder('Primer Apellido')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('maternal_last_name')
                    ->label('Apellido Materno')
                    ->placeholder('Primer Segundo')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->label('Dirección')
                    ->placeholder('Ingresa la direccion del empleado (opcional)')
                    ->maxLength(255)
                    ->nullable(),
                Forms\Components\TextInput::make('phone')
                    ->placeholder('3324657676')
                    ->label('Teléfono')
                    ->tel()
                    ->required()
                    ->unique(ignoreRecord:true)
                    ->maxLength(10),
                Forms\Components\Toggle::make('active')
                    ->label('Activo')
                    ->required()
                    ->default(true)
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Empleados')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('paternal_last_name')
                    ->label('Apellido Paterno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('maternal_last_name')
                    ->label('Apellido Materno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('active')
                    ->label('Activo')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle')
                    ->searchable(),
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
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('activos')
                    ->options([
                        true => 'Activos',
                        false => 'No Activos'
                    ])->attribute('active')
                    ->native(false)
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->before(function (Employee $record) {
                            // dd($record);
                            $record->update(['is_available' => false]);
                        }),
                    Tables\Actions\RestoreAction::make()
                        ->after(function (Employee $record) {
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
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
