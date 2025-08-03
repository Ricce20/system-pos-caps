<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Venta';
    
    protected static ?string $navigationLabel = 'Ventas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('sale_date')
                    ->label('Fecha de venta')
                    ->required(),
                Forms\Components\Select::make('employee_id')
                    ->relationship('employee','name')
                    ->label('Empleado'),
                Forms\Components\Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user','name')
                    ->required(),
                Forms\Components\TextInput::make('location_id')
                    ->label('Sucursal')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('cash_register_id')
                    ->label('Caja')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total')
                    ->label('Total')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('method_of_payment')
                    ->label('Metodo de pago')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Registro de ventas')
            ->columns([
                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Fecha venta')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->default('No asignado')
                    ->label('Empleado')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable(),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Sucursal')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cashRegister.name')
                    ->label('Caja')
                    ->sortable(),
                // Tables\Columns\TextColumn::make('total')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\IconColumn::make('is_check')
                //     ->boolean(),
                // Tables\Columns\TextColumn::make('method_of_payment'),
                // Tables\Columns\TextColumn::make('deleted_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
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
            RelationManagers\SaleDetailRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'view' => Pages\ViewSale::route('/{record}'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
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
