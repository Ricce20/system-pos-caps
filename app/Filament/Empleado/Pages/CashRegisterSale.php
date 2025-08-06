<?php

namespace App\Filament\Empleado\Pages;

use App\Models\CashRegister;
use App\Models\CashRegisterDetail;
use App\Models\Sale;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithRecord;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashRegisterSale extends Page
{
    // use InteractsWithRecord;

    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.empleado.pages.cash-register-sale';

    protected static ?string $title = 'Caja registradora';

    protected static ?string $slug = 'cash/{record}/sale';

    protected static bool $shouldRegisterNavigation = false;

    public $opened = false;
    public $cashregisterdetails;
    public $record;

    public function mount(int | string $record): void
    {
        // Busca el registro de CashRegister por ID o lanza una excepción si no lo encuentra
        $this->record = CashRegister::findOrFail($record);
        
        // Validar si la caja registradora está disponible
        if (!$this->record->is_available) {
            Notification::make()
                ->warning()
                ->persistent()
                ->title('Caja Registradora No Disponible')
                ->body('La caja registradora seleccionada no está disponible en este momento.')
                ->send();
                
            redirect()->back();
        }
        
        $this->cashregisterdetails = CashRegisterDetail::where('cash_register_id', $record)
                                        ->whereNull('end_date')         // Para asegurarte de que 'end_date' es nulo
                                        ->first();
        if($this->cashregisterdetails)  $this->opened = true;              // Para obtener el primer resultado o fallar si no lo encuentra
    }
    protected function getHeaderActions(): array
    {
        return [
            
            Action::make('Abrir caja')
                ->form([
                    TextInput::make('openingBalance')
                    ->label('Cantidad de apertura')
                    ->numeric()
                    ->required()
                    ->inputMode('decimal')
                    ->prefix('$')
                    ->suffix('MXN')
                    ->minValue(100)
                    ,

                ])
                ->action(function (array $data,$record): void {
                    
                    $this->createCashRegisterDetail($data);
                    $this->opened=true;
                    //notificamos
                    Notification::make()
                    ->success()
                    ->persistent()
                    ->title('Caja abierta correctamente')
                    ->send();
                })
                ->requiresConfirmation()
                ->disabled($this->opened),

           
            Action::make('Cortar caja')
                ->form([
                    TextInput::make('counted_amount')
                    ->label('Cantidad de cierre')
                    ->numeric()
                    ->inputMode('decimal')
                    ->prefix('$')
                    ->suffix('MXN')
                    ->minValue(0)
                    ->required(),

                ])
                ->action(function (array $data): void {
                   
                    $this->createClosedCash($data);
                    $this->opened=false;
                    //notificamos
                    Notification::make()
                    ->success()
                    ->persistent()
                    ->title('Caja cerrada correctamente')
                    ->send();

                    $this->cashregisterdetails = null;


                })
                ->requiresConfirmation()
                ->disabled(!$this->opened),
        ];
    }

    private function createCashRegisterDetail(array $data){
        try {
            //crear el detalle de la caja
            DB::transaction(function() use($data){
                $horaLocal = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');

                $cashregisterdetail = new CashRegisterDetail();
                $cashregisterdetail->starting_quantity = $data['openingBalance'];
                $cashregisterdetail->cash_register_id = $this->record->id;
                // $cashregisterdetail->openedAndClosedBy = auth()->user()->name;
                $cashregisterdetail->start_date = $horaLocal;
                $cashregisterdetail->save();

                 //guardamos el detalle
                $this->cashregisterdetails = $cashregisterdetail;
            });
            
        } catch (Exception $e) {
            Notification::make()
            ->warning()
            ->persistent()
            ->title('Hubo un problema al abrir la caja, intente mas tarde')
            ->send();
            Log::error('ERROR EN ACCION DE APERTURA DE CAJA: '.$e->getMessage());

        }
       
    }

    private function createClosedCash(array $data)
    {
        try {
            DB::transaction(function () use ($data) {
                // Fecha/hora exacta del inicio
                // Obtenemos la fecha de hoy del detalle de caja, solo la fecha sin hora
                $startDate = \Carbon\Carbon::parse($this->cashregisterdetails->start_date)->format('Y-m-d');
                // $startDate = $this->cashregisterdetails->start_date;
                // dd($this->record->id);
                // Suma de ventas desde el inicio de la caja
                $salesTotal = Sale::where('cash_register_id', $this->record->id)
                    ->where('sale_date', '>=', $startDate)
                    ->where('is_check', false)
                    ->sum('total');
                

                $total = $salesTotal + floatval($this->cashregisterdetails->starting_quantity);

                // Actualizamos detalle de caja
                $this->cashregisterdetails->update([
                    'closing_amount' => $total,
                    'counted_amount' => $data['counted_amount'],
                    'end_date' => now(),
                ]);

                // Marcamos ventas como check
                Sale::where('cash_register_id', $this->record->id)
                    ->where('sale_date', '>=', $startDate)
                    // ->where('user_id',auth()->user()->id)
                    ->where('is_check', false)
                    ->update(['is_check' => true]);
            });

        } catch (\Exception $e) {
            Notification::make()
                ->warning()
                ->persistent()
                ->title('Hubo un problema al cerrar la caja, intente más tarde')
                ->body('Problema: ' . $e->getMessage())
                ->send();

            Log::error('ERROR EN ACCION DE CIERRE DE CAJA: ' . $e);
        }
    }
    
}
