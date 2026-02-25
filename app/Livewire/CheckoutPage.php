<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CartItem;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ShippingService; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CheckoutPage extends Component
{
    public $cartItems = [];
    public $subtotal = 0;
    public $shippingPrice = 0;
    public $discount = 0;
    public $total = 0;

    public $selectedAddressId = null;
    public $shippingMethod = null; 
    public $shippingOptions = [];  
    public $paymentMethod = 'credit_card';
    public $couponCode = '';
    
    public $cpf;
    public $phone;
    public $fullName;

    public $useNewAddress = false;
    public $newAddress = [
        'zip_code' => '', 'street' => '', 'number' => '', 
        'complement' => '', 'neighborhood' => '', 'city' => '', 'state' => ''
    ];

    protected ShippingService $shippingService;

    public function boot(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    protected function rules()
    {
        $rules = [
            'fullName' => 'required|min:3',
            'cpf' => 'required|min:11',
            'phone' => 'required|min:10',
            'shippingMethod' => 'required|string',
            'paymentMethod' => 'required|string',
        ];

        if ($this->useNewAddress || Auth::user()->addresses->isEmpty()) {
            $rules['newAddress.zip_code'] = 'required|min:8';
            $rules['newAddress.street'] = 'required';
            $rules['newAddress.number'] = 'required';
            $rules['newAddress.neighborhood'] = 'required';
            $rules['newAddress.city'] = 'required';
            $rules['newAddress.state'] = 'required|max:2';
        } else {
            $rules['selectedAddressId'] = 'required|exists:addresses,id';
        }

        return $rules;
    }

    // Tradução das mensagens de erro para o usuário
    protected function messages()
    {
        return [
            'fullName.required' => 'O nome completo é obrigatório.',
            'cpf.required' => 'O CPF é obrigatório.',
            'phone.required' => 'O telefone é obrigatório.',
            'shippingMethod.required' => 'Selecione uma opção de frete.',
            'newAddress.zip_code.required' => 'O CEP é obrigatório.',
            'newAddress.zip_code.min' => 'O CEP deve ter 8 dígitos.',
            'newAddress.street.required' => 'A rua é obrigatória.',
            'newAddress.number.required' => 'O número do endereço é obrigatório.',
            'newAddress.neighborhood.required' => 'O bairro é obrigatório.',
            'newAddress.city.required' => 'A cidade é obrigatória.',
            'newAddress.state.required' => 'O estado é obrigatório.',
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        $this->fullName = $user->name;
        $this->cpf = $user->cpf ?? ''; 
        $this->phone = $user->phone ?? '';

        if ($user->addresses->isNotEmpty()) {
            $this->selectedAddressId = $user->addresses->first()->id;
        } else {
            $this->useNewAddress = true;
        }

        $this->loadCart();
        $this->calculateTotals();

        if ($this->selectedAddressId) {
            $address = Address::find($this->selectedAddressId);
            if ($address) {
                $this->shippingOptions = $this->shippingService->calculate($address->zip_code, $this->cartItems);
            }
        }
    }

    public function updatedSelectedAddressId($value)
    {
        if ($value) {
            $this->useNewAddress = false;
            $address = Address::find($value);
            if ($address) {
                $this->shippingOptions = $this->shippingService->calculate($address->zip_code, $this->cartItems);
            }
            $this->resetShippingSelection();
        }
    }

    public function updatedUseNewAddress($value)
    {
        if ($value) {
            $this->selectedAddressId = null;
            $this->shippingOptions = []; 
            $cep = preg_replace('/\D/', '', $this->newAddress['zip_code'] ?? '');
            if (strlen($cep) === 8) {
                $this->shippingOptions = $this->shippingService->calculate($cep, $this->cartItems);
            }
            $this->resetShippingSelection();
        }
    }

    public function updated($propertyName, $value)
    {
        if ($propertyName === 'newAddress.zip_code') {
            $cep = preg_replace('/\D/', '', $value);
            
            if (strlen($cep) === 8) {
                $this->fetchAddressFromCep($cep);
                
                // Só calcula o frete se o CEP for válido e existir
                if (!$this->getErrorBag()->has('newAddress.zip_code')) {
                    $this->shippingOptions = $this->shippingService->calculate($cep, $this->cartItems);
                } else {
                    $this->shippingOptions = [];
                }
            } else {
                $this->shippingOptions = [];
            }
            $this->resetShippingSelection();
        }
    }

    private function fetchAddressFromCep($cep)
    {
        try {
            $response = Http::get("https://viacep.com.br/ws/{$cep}/json/");
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Se a API retornar erro (CEP formato certo, mas não existe)
                if (isset($data['erro']) && $data['erro'] == true) {
                    $this->addError('newAddress.zip_code', 'CEP inválido ou não encontrado.');
                    $this->newAddress['street'] = '';
                    $this->newAddress['neighborhood'] = '';
                    $this->newAddress['city'] = '';
                    $this->newAddress['state'] = '';
                } else {
                    $this->resetErrorBag('newAddress.zip_code');
                    $this->newAddress['street'] = $data['logradouro'] ?? '';
                    $this->newAddress['neighborhood'] = $data['bairro'] ?? '';
                    $this->newAddress['city'] = $data['localidade'] ?? '';
                    $this->newAddress['state'] = $data['uf'] ?? '';
                }
            }
        } catch (\Exception $e) {
            $this->addError('newAddress.zip_code', 'Erro ao buscar o CEP. Preencha manualmente.');
        }
    }

    public function updatedShippingMethod($value)
    {
        $option = collect($this->shippingOptions)->firstWhere('id', $value);
        $this->shippingPrice = $option ? (float) $option['price'] : 0;
        $this->calculateTotals();
    }

    private function resetShippingSelection()
    {
        $this->shippingMethod = null;
        $this->shippingPrice = 0;
        $this->calculateTotals();
    }

    public function loadCart()
    {
        $this->cartItems = CartItem::with(['product', 'variant'])
            ->where('user_id', Auth::id())
            ->get();

        if ($this->cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'Seu carrinho está vazio.');
        }
    }

    public function calculateTotals()
    {
        $this->cartItems = CartItem::with(['product', 'variant'])
            ->where('user_id', Auth::id())
            ->get();

        $sub = 0;
        foreach ($this->cartItems as $item) {
            $sub += $item->total ?? ($item->quantity * ($item->variant ? $item->variant->price : $item->product->base_price));
        }
        $this->subtotal = $sub;

        $this->total = ($this->subtotal + $this->shippingPrice) - $this->discount;
    }

    public function applyCoupon()
    {
        if (strtoupper($this->couponCode) === 'DESCONTO10') {
            $this->discount = $this->subtotal * 0.10;
            session()->flash('coupon_success', 'Cupom aplicado com sucesso!');
        } else {
            $this->discount = 0;
            $this->addError('couponCode', 'Cupom inválido ou expirado.');
        }
        $this->calculateTotals();
    }

    public function placeOrder()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            $address = null;
            if ($this->useNewAddress || Auth::user()->addresses->isEmpty()) {
                $address = Auth::user()->addresses()->create($this->newAddress);
            } else {
                $address = Address::find($this->selectedAddressId);
            }

            $order = Order::create([
                'user_id' => Auth::id(),
                'status' => Order::STATUS_PENDING,
                'total_price' => $this->total,
                'shipping_price' => $this->shippingPrice,
                'discount' => $this->discount,
                'payment_method' => $this->paymentMethod,
                'address_json' => $address->toArray(), 
            ]);

            foreach ($this->cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->total / $item->quantity,
                ]);
            }

            CartItem::where('user_id', Auth::id())->delete();
            DB::commit();

            return redirect()->route('profile.orders')->with('status', 'Pedido realizado com sucesso! Aguardando pagamento.');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Ocorreu um erro ao processar seu pedido. Tente novamente.');
        }
    }

    public function render()
    {
        return view('livewire.checkout-page')->layout('components.layout', ['title' => 'Checkout Segura']);
    }
}