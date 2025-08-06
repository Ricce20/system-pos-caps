
{{-- @vite('resources/css/app.css') --}}
<div class="">       
    
    <!-- Campo para ingresar el código del producto -->
    <div class="flex justify-between ">

        {{-- input add items --}}
        <div class="w-1/2">     
            <div class="mb-2">

                {{ $this->form }}                  
            </div>
            
            {{-- details shop --}}
                
            <div>

                <x-filament::section>
                    <x-slot name="heading">
                        Total a pagar: ${{$this->total}} MXN
                    </x-slot>

                    <x-filament::fieldset >
                        <x-slot name="label">
                            Forma de pago
                            </x-slot>
                               
                        <x-filament::input.select wire:model.lazy="method">
                            <option selected ="">Select</option>
                                @foreach($Paymentmethods as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach

                        </x-filament::input.select>
                    </x-filament::fieldset>



                    <x-filament::fieldset >
                            
                        <x-slot name="label">
                            Recibido
                            </x-slot>
                            <x-filament::input.wrapper>
                                <x-slot name="prefix">
                                    $
                                </x-slot>
                            <x-filament::input
                                type="number"
                                min="0"
                                wire:model.lazy="recibido"
                                label="Cantidad Recibida"
                                />
                            </x-filament::input.wrapper>
                            
                        </x-filament::fieldset>

                        <div class="mt-2">
                            {{ $this->deleteAction }}
                         
                            <x-filament-actions::modals />
                        </div>
                            
                    </x-filament::section>

                </div>

        </div>

       
       
         <!-- Lista de productos -->
        <div class="w-full px-6 mx-3">
            @if(count($cart) > 0)
                <x-filament::button 
                    color="danger"
                    icon="heroicon-m-trash"
                    wire:click="clearList"
                    label="Limpiar"
                    size="xs">
                    Limpiar
                </x-filament::button>

                <x-filament::button 
                    color="info"
                    icon="heroicon-m-pencil-square"
                    wire:click="saveChanges"
                    label="Limpiar"
                    size="xs">
                    Guardar Lista
                </x-filament::button>
            @endif
          
                <h2 class="text-center p-3 mb-3">Productos</h2>

                <div class="">
                    <ul class="">
                        @foreach($this->cart as $product)
                            <li wire:key="product-{{ $product['id']}}" class="border-b p-3">
                                {{ $product['full_name'] }}  - ${{ $product['price'] }} - Unid: {{$product['quantity'] }} -
                                
                                <x-filament::button color="success"
                                icon="heroicon-m-plus"
                                wire:click="incrementQuantity({{ $product['id'] }})"
                                label="Agregar"
                                size="xs">
                                </x-filament::button>
                                -
                                <x-filament::button color="warning" 
                                icon="heroicon-m-minus"
                                wire:click="descountQuantityProduct({{ $product['id'] }})"
                                label="Agregar"
                                size="xs">
                                </x-filament::button>
                                -
                                <x-filament::button color="danger"
                                icon="heroicon-m-trash"
                                wire:click="deleteProductById({{ $product['id'] }})"
                                label="Agregar"
                                size="xs">
                                </x-filament::button>
                               
                            </li>
                        @endforeach
                    </ul>
                </div>
               
        </div>
                
        
    </div>

    <br>
    <hr>
    <br>

   

   

</div>
