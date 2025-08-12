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
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;

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
                    ->label('Sucursal asignada')
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
                    ->label('Sucursal asignada')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario asignado')
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
                Tables\Filters\SelectFilter::make('Disponibilidad')
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
                    //     ->before(function (CashRegister $record) {
                    //         // dd($record);
                    //         $record->update(['is_available' => false]);
                    //     }),
                    // Tables\Actions\RestoreAction::make()
                    //     ->after(function (CashRegister $record) {
                    //         $record->update(['is_available' => true]);
                    //     }),
                    Tables\Actions\Action::make('Ir a caja')
                        ->visible(fn (Model $record): bool => auth()->user()->id === $record->user_id)
                        ->hidden(fn(Model $record)=> !$record->location->active || !$record->is_available)
                        ->label('Ir a caja')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->url(fn (CashRegister $record): string => route('filament.admin.resources.cash-registers.cash', ['record' => $record])),
                    Tables\Actions\Action::make('generar_corte_caja')
                        ->label('Generar Reporte de Corte')
                        ->color('success')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-document-arrow-down')
                        ->form([
                            Select::make('report_type')
                                ->label('Tipo de Reporte')
                                ->options([
                                    'daily' => 'Corte del Día',
                                    'weekly' => 'Corte de la Semana',
                                    'monthly' => 'Corte del Mes',
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
                        ->action(function (array $data, CashRegister $record) {
                            // Validar fechas para rangos personalizados
                            if ($data['report_type'] === 'custom' && (!$data['start_date'] || !$data['end_date'])) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Las fechas son requeridas para rangos personalizados')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Obtener los detalles de corte según el tipo de reporte y caja específica
                            $query = \App\Models\CashRegisterDetail::query()
                                ->where('cash_register_id', $record->id);
                            
                            switch ($data['report_type']) {
                                case 'daily':
                                    $query->whereDate('start_date', Carbon::today());
                                    break;
                                case 'weekly':
                                    $query->whereBetween('start_date', [
                                        Carbon::now()->startOfWeek(),
                                        Carbon::now()->endOfWeek()
                                    ]);
                                    break;
                                case 'monthly':
                                    $query->whereMonth('start_date', Carbon::now()->month)
                                          ->whereYear('start_date', Carbon::now()->year);
                                    break;
                                case 'custom':
                                    $query->whereBetween('start_date', [
                                        Carbon::parse($data['start_date'])->startOfDay(),
                                        Carbon::parse($data['end_date'])->endOfDay()
                                    ]);
                                    break;
                            }

                            $records = $query->with(['cashRegister', 'cashRegister.location', 'cashRegister.user'])->get();
                            
                            if ($records->isEmpty()) {
                                Notification::make()
                                    ->title('Sin datos')
                                    ->body('No hay cortes de caja para el período seleccionado')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $countDetails = $records->count();
                            $sumAmount = $records->sum('counted_amount');
                            $minDate = $records->min('start_date');
                            $maxDate = $records->max('end_date');
                            $horaLocal = Carbon::now('America/Mexico_City')->format('d/m/Y H:i');

                            return response()->streamDownload(function () use ($records, $countDetails, $sumAmount, $horaLocal, $minDate, $maxDate, $record) {
                                echo Pdf::loadHtml(
                                    Blade::render('pdf-cash-register-detail', [
                                        'records' => $records, 
                                        'total' => $sumAmount,
                                        'cantidad' => $countDetails,
                                        'fecha' => $horaLocal,
                                        'minDate' => $minDate,
                                        'maxDate' => $maxDate,
                                        'cashRegister' => $record
                                    ])
                                )->stream();
                            }, 'Reporte de corte de caja ' . $record->name . '-' . now()->format('Y-m-d_H-i-s') . '.pdf');
                        }),
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
