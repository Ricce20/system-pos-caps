<?php

namespace App\Livewire;

use App\Models\Item;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SupplierItem;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Type\Integer;

class ScanerComponent extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public $record;
    public ?array $data = [];
    public Collection $cart;
    public float $total = 0;
    public $cambio = 0;
    public $recibido = 0;
    public $method;

    public string $keyCart;
    public string $keyTotal;

    public $warehouseId;
    public $locationId;

    public $Paymentmethods;

    public function render()
    {
        return view('livewire.scaner-component');
    }

    public function mount($record)
    {
        $this->record = $record->toArray();
        $this->form->fill();

        $this->locationId = $record->location->id;
        $this->warehouseId = $record->location->warehouse->id;

        // dd($this->warehouseId,$this->locationId);
        $this->Paymentmethods = [
            'Efectivo' => 'Efectivo',
            'Tarjeta' => 'Tarjeta',
        ];

        $this->keyCart = "cart_products-cash-{$record->id}-location-{$this->locationId}";
        $this->keyTotal = "cart_total-cash-{$record->id}-location-{$this->locationId}";

        $this->total = floatval(Session::get($this->keyTotal, 0));
        $this->cart = collect(Session::get($this->keyCart, []));
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('barcode')
                ->label('Código de Barras')
                ->placeholder('12345678901')
                ->minLength(8)
                ->maxLength(14)
                ->live(onBlur: true)
                ->reactive()
                ->afterStateUpdated(function (?string $state) {
                    if (strlen($state) >= 8 && strlen($state) <= 14) {
                        $this->ProccessAddProductInCollection(code: $state);
                    }
                }),
        ])->statePath('data');
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->requiresConfirmation()
            ->visible(fn () => $this->cart->count() > 0 && $this->recibido >= $this->total && $this->method !== null)
            ->label('Confirmar compra')
            ->action(fn () => $this->payProccess());
    }

    public function ProccessAddProductInCollection(string $code)
    {
        try {
            $product = Item::with(['product', 'size'])
                ->where('code', $code)
                ->whereNull('deleted_at')
                ->where('is_available', true)
                ->first();

            // dd($product);
            if (blank($product)) return;

            $product->quantity = 1;

            $exists = $this->verifyProductInCollectionProducts($product);

            if(!$this->availableWarehouse()){
                Notification::make()
                ->title('Almacen inactivo')
                ->info()
                ->body('El almacen no se encuentra activo para realizar ventas')
                ->send();
                $this->form->fill();
                return;
            }

            if (!$exists) {
                if (!$this->hasStock($product->id,$product->quantity)) {
                    Notification::make()
                        ->title('Stock insuficiente')
                        ->info()
                        ->body('No hay suficiente stock para: ' . optional($product->product)->name)
                        ->send();
                    $this->form->fill();
                    return true;
                }

                $this->addProductInCollectionProduct($product);
            }

            $this->form->fill();
            return;
        } catch (\Exception $e) {
            $this->notifyError('Error al agregar el producto', $e->getMessage());
            Log::alert('ERROR AGREGANDO PRODUCTO: ' . $e);
            return;
        }
    }

    private function addProductInCollectionProduct(object $product): bool
    {
        try {
            $product->loadMissing(['product', 'size']);
            $full_name =  optional($product->product)->name . ' - ' . optional($product->size)->name;
            $price = $this->getPrimaryPrice($product);

            if (empty($price) || $price == null) {
                Notification::make()
                    ->title('Sin proveedor primario')
                    ->danger()
                    ->body('El producto: ' . $product->full_name . ' no tiene proveedor asignado con precio.')
                    ->send();
                return false;
            }

            $this->cart->push([
                'id' => $product->id,
                'full_name' => $full_name,
                'price' => $price,
                'quantity' => 1
            ]);


            $this->total += floatval($price);
            return true;
        } catch (\Exception $e) {
            $this->notifyError('Error al agregar el producto', $e->getMessage());
            return false;
        }
    }

    public function verifyProductInCollectionProducts(object $product): bool
    {
        try {
            if (blank($product)) return true;

            $productIndex = $this->cart->search(fn($item) => $item['id'] === $product['id']);

            if ($productIndex === false) return false;

            $existing = $this->cart->get($productIndex);
            $existing['quantity'] += 1;

            if (!$this->hasStock($product->id,$product->quantity)) {
                Notification::make()
                    ->title('Stock insuficiente')
                    ->info()
                    ->body('No hay suficiente stock para: ' . optional($product->product)->name)
                    ->send();
                return true;
            }

            $this->cart->put($productIndex, $existing);
            $this->total += floatval($existing['price']);
            return true;
        } catch (\Exception $e) {
            $this->notifyError('Error al verificar el producto', $e->getMessage());
            return true;
        }
    }


    public function incrementQuantity(string|int $id)
    {
        $indexProduct = $this->cart->search(fn ($item) => $item['id'] === $id);
        // dd($indexProduct);
        if($indexProduct === false) return;

        $product = $this->cart->get($indexProduct);
        // dd($product);
        if(!$this->hasStock($product['id'],$product['quantity'] + 1)){
            Notification::make()
                    ->title('Stock insuficiente')
                    ->info()
                    ->body('No hay suficiente stock para: ' . $product['full_name'])
                    ->send();
                return;
        }

        $product['quantity'] += 1;

        $this->total += $product['price'];
        $this->cart->put($indexProduct,$product);
        return;
    }

    public function descountQuantityProduct(string|int $id)
    {
        $indexProduct = $this->cart->search(fn ($item) => $item['id'] === $id);
        // dd($indexProduct);
        if($indexProduct === false) return;

        $product = $this->cart->get($indexProduct);
       

        if($product['quantity'] > 1){
            $product['quantity'] -= 1;
        }else{
            $this->deleteProductById($id);
            return;
        }

        $this->total -= $product['price'];
        $this->cart->put($indexProduct,$product);
        return;
    }

    public function deleteProductById(string|int $id)
    {
        $indexProduct = $this->cart->search(fn ($item) => $item['id'] === $id);
        // dd($indexProduct);
        if($indexProduct === false) return;

        $product = $this->cart->get($indexProduct);
       

        $this->total -= floatval($product['price'] * $product['quantity']);
        $this->cart->forget($indexProduct);
        return;
    }

    private function payProccess(){
        //creamos la compra
        $complete = $this->registerSale();
        //si es hay un errror la venta no se realiza
        if(!$complete){
            Notification::make()
            ->title('Ocurrio algo realizar la venta')
            ->danger()
            ->body('Ocurrió un error al realizar la venta, intente nuevamente' )
            ->send()
            ->persistent();
            return;
        };

        $this->cambio =  (floatval($this->recibido) - $this->total);
      //  dd($this->cambio);

        $this->clearInputs();
        $this->clearList();

        Notification::make()
        ->title('Venta Completada')
        ->success()
        ->body('Cambio: $'.$this->cambio)
        ->persistent()
        ->send();

        $this->recibido = 0;
        return;
    }

    private function registerSale(): bool
    {
        try {
            DB::transaction(function () {
                $horaLocal = Carbon::now();
                // dd($this->record['id']);

                $SaleData = [
                    'sale_date' => $horaLocal,
                    'total' => $this->total,
                    'location_id' => $this->locationId,
                    'cash_register_id' => $this->record['id'],
                    'method_of_payment' => $this->method,
                    'is_check' => false,
                    'user_id'=> auth()->user()->id,
                    'employee_id' => auth()->user()->employee()->id ?? null
                ];
                //   dd($SaleData);
                $sale = Sale::create($SaleData);
                // dd($sale);
                $saleDetailsData = [];
                foreach ($this->cart as $item) {
                    $saleDetailsData[] = [
                        'sale_id' => $sale->id,
                        'item_id' => $item['id'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['price'] * $item['quantity'],
                    ];

                    $this->DescountStockProductInWarehouse(
                        product_id:$item['id'], 
                        quantity:$item['quantity']
                    );
                }
                // dd($sale);
                // Inserción masiva con createMany() a través de la relación
                SaleDetail::insert($saleDetailsData);
            });

            return true;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al realizar la venta')
                ->danger()
                ->body('Ocurrió un error al realizar la venta: ' . $e->getMessage())
                ->send()
                ->persistent();

            Log::error('ERROR EN EL PROCESO DE REALIZAR EL PAGO: ' .$e->getMessage());
            return false;
        }
    }

    //----------------------------------------------------------

    private function DescountStockProductInWarehouse(int|string $product_id, int $quantity): bool
    {
        try {
            return DB::table('warehouse_items')->where('item_id',$product_id)
            ->where('warehouse_id',$this->warehouseId)
            ->decrement('stock',$quantity);
        } catch (\Exception $e) {
            Log::alert('PROBLEMA EN EL PROCESO DE DESCONTAR STOCK DEL WAREHOUSE_PRODUCT: '. $e->getMessage());
            return false;
        }
    }

    private function hasStock(string|int $id, int $quantity): bool
    {
        return WarehouseItem::where('item_id', $id)
            ->where('warehouse_id', $this->warehouseId)
            ->where('is_available', true)
            ->where('stock', '>=', $quantity)
            ->exists();
    }

    private function availableWarehouse():bool{
        return Warehouse::where('id',$this->warehouseId)
            ->where('active',true)->exists();
    }

    private function getPrimaryPrice(Item $product): float
    {
        return (float) SupplierItem::where('item_id', $product->id)
            ->where('is_primary', true)
            ->where('is_available',true)
            ->value('sale_price') ?? 0;
    }

    public function saveChanges()
    {
        try {
            Session::put($this->keyCart, $this->cart);
            Session::put($this->keyTotal, $this->total);

            Notification::make()
                ->title('Cambios guardados')
                ->success()
                ->body('Los cambios se han guardado correctamente')
                ->send();
        } catch (\Exception $e) {
            $this->notifyError('Error al guardar los cambios', $e->getMessage());
        }
    }

    public function clearList()
    {
        session()->forget($this->keyCart);
        session()->forget($this->keyTotal);

        $this->cart = collect();
        $this->total = 0;
    }

    private function notifyError(string $title, string $message)
    {
        Notification::make()
            ->title($title)
            ->danger()
            ->body('Detalles: ' . $message)
            ->send();
    }

    private function clearInputs()
    {
        $this->method = 0;
        $this->recibido = 0;
    }
}
