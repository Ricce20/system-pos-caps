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
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Barryvdh\DomPDF\Facade\Pdf;

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
                    ->label('Empleado')
                    ->placeholder('Selecciona un empleado')
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user','name')
                    ->required(),
                Forms\Components\Select::make('location_id')
                    ->label('Sucursal')
                    ->relationship('location','name')
                    ->required(),
                Forms\Components\Select::make('cash_register_id')
                    ->label('Caja')
                    ->required()
                    ->relationship('cashRegister','name'),
                Forms\Components\TextInput::make('total')
                    ->label('Total')
                    ->prefix('$')
                    ->suffix('MXN')
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
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->numeric()
                    ->prefix('Folio-')
                    ->sortable()
                    ->label('Folio'),
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
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->prefix('$')
                    ->suffix('MXN')
                    ->numeric()
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()->prefix('$')->label('Total Ingresos'),
                        Tables\Columns\Summarizers\Average::make()->prefix('$')->label('Promedio'),
                        Tables\Columns\Summarizers\Count::make()->label('Total ventas'),
                    ]),
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
               
            ])
            ->headerActions([
                Tables\Actions\Action::make('generate_pdf_report')
                    ->label('Generar Informe PDF')
                    ->color('success')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-document-arrow-down')
                    ->form([
                        Select::make('report_type')
                            ->label('Tipo de Informe')
                            ->options([
                                'daily' => 'Ventas del Día',
                                'weekly' => 'Ventas de la Semana',
                                'monthly' => 'Ventas del Mes',
                                'custom' => 'Rango Personalizado',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state !== 'custom') {
                                    $set('start_date', null);
                                    $set('end_date', null);
                                }
                            }),

                        Select::make('location_id')
                            ->label('Sucursal')
                            ->options(function () {
                                return \App\Models\Location::where('active', true)
                                    ->pluck('name', 'id')
                                    ->prepend('Todas las Sucursales', 'all');
                            })
                            ->default('all')
                            ->required()
                            ->searchable()
                            ->preload(),

                        DatePicker::make('start_date')
                            ->label('Fecha de Inicio')
                            ->visible(fn (callable $get) => $get('report_type') === 'custom')
                            ->required(fn (callable $get) => $get('report_type') === 'custom'),

                        DatePicker::make('end_date')
                            ->label('Fecha de Fin')
                            ->visible(fn (callable $get) => $get('report_type') === 'custom')
                            ->required(fn (callable $get) => $get('report_type') === 'custom')
                            ->after('start_date'),
                    ])
                    ->action(function (array $data) {
                        // Validar fechas para rangos personalizados
                        if ($data['report_type'] === 'custom' && (!$data['start_date'] || !$data['end_date'])) {
                            Notification::make()
                                ->title('Error')
                                ->body('Las fechas son requeridas para rangos personalizados')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Obtener las ventas según el tipo de reporte
                        $query = \App\Models\Sale::query();
                        
                        // Filtrar por sucursal si se selecciona una específica
                        if ($data['location_id'] && $data['location_id'] !== 'all') {
                            $query->where('location_id', $data['location_id']);
                        }
                        
                        switch ($data['report_type']) {
                            case 'daily':
                                $query->whereDate('sale_date', Carbon::today());
                                break;
                            case 'weekly':
                                $query->whereBetween('sale_date', [
                                    Carbon::now()->startOfWeek(),
                                    Carbon::now()->endOfWeek()
                                ]);
                                break;
                            case 'monthly':
                                $query->whereMonth('sale_date', Carbon::now()->month)
                                      ->whereYear('sale_date', Carbon::now()->year);
                                break;
                            case 'custom':
                                $query->whereBetween('sale_date', [
                                    Carbon::parse($data['start_date'])->startOfDay(),
                                    Carbon::parse($data['end_date'])->endOfDay()
                                ]);
                                break;
                        }

                        $records = $query->with(['employee', 'user', 'location', 'cashRegister'])->get();
                        
                        if ($records->isEmpty()) {
                            Notification::make()
                                ->title('Sin datos')
                                ->body('No hay ventas para el período seleccionado')
                                ->warning()
                                ->send();
                            return;
                        }

                        $countSales = $records->count();
                        $sumSales = $records->sum('total');
                        $minDate = $records->min('sale_date');
                        $maxDate = $records->max('sale_date');
                        $horaLocal = Carbon::now('America/Mexico_City')->format('d/m/Y H:i');

                        // Obtener información de la sucursal para el nombre del archivo
                        $locationName = 'Todas';
                        if ($data['location_id'] && $data['location_id'] !== 'all') {
                            $location = \App\Models\Location::find($data['location_id']);
                            $locationName = $location ? $location->name : 'Desconocida';
                        }

                        return response()->streamDownload(function () use ($records, $countSales, $sumSales, $horaLocal, $minDate, $maxDate, $locationName) {
                            echo Pdf::loadHtml(
                                Blade::render('pdf-sales', [
                                    'records' => $records, 
                                    'total' => $sumSales,
                                    'cantidad' => $countSales,
                                    'fecha' => $horaLocal,
                                    'minDate' => $minDate,
                                    'maxDate' => $maxDate,
                                    'locationName' => $locationName
                                ])
                            )->stream();
                        }, 'Reporte de ventas ' . $locationName . '-' . now()->format('Y-m-d_H-i-s') . '.pdf');
                    }),
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
