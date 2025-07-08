<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UsersEmployeeResource\Pages;
use App\Filament\Resources\UsersEmployeeResource\RelationManagers;
use App\Models\User;
use App\Models\UsersEmployee;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class UsersEmployeeResource extends Resource
{
    protected static ?string $model = UsersEmployee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Asignacion';
    
    protected static ?string $navigationLabel = 'Asignacion de Usuarios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('id', '!=', Auth::id())->where('is_available',true)
                    )
                    ->preload(),
                Forms\Components\Select::make('employee_id')
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->relationship('employee', 'name')
                    ->preload(),
                Forms\Components\Toggle::make('online')
                    ->required(),
                Forms\Components\Toggle::make('active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Empleado')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('online')
                    ->label('En línea')
                    ->boolean(),
                Tables\Columns\IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha de inicio')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha de fin')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Finalizar')
                    ->label('Finalizar relación')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Finalizar relación?')
                    ->modalDescription('Esta acción finalizará la relación entre el usuario y el empleado. ¿Estás seguro?')
                    ->modalSubmitActionLabel('Sí, finalizar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (UsersEmployee $record) {
                        // Actualizar la fecha de fin
                        $record->update([
                            'end_date' => Carbon::now(),
                            'active' => false, // También marcar como inactivo
                            'online'=> false
                        ]);

                        // Marcar el usuario como disponible
                        $user = User::find($record->user_id);
                        if ($user) {
                            $user->update(['is_available' => true]);
                        }
                    })
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Relación finalizada')
                            ->body('La relación entre usuario y empleado ha sido finalizada exitosamente.')
                    )
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
            'index' => Pages\ListUsersEmployees::route('/'),
            'create' => Pages\CreateUsersEmployee::route('/create'),
            'view' => Pages\ViewUsersEmployee::route('/{record}'),
            'edit' => Pages\EditUsersEmployee::route('/{record}/edit'),
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
