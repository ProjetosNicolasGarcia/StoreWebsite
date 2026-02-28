<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CartItem;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
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
    public $appliedCouponId = null; 
    public $couponDisplay = ''; 
    
    public $cpf;
    public $phone;
    public $firstName;
    public $lastName;

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
            'firstName' => 'required|min:2',
            'lastName' => 'required|min:2',
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

    protected function messages()
    {
        return [
            'firstName.required' => 'O nome é obrigatório.',
            'lastName.required' => 'O sobrenome é obrigatório para faturamento.',
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
        // 1. O método fresh() atualiza a instância da sessão com os dados mais recentes do banco,
        // garantindo que a nova coluna 'last_name' seja reconhecida.
        $user = Auth::user()->fresh();

        // 2. Refatorado de fullName para name e lastName com proteção contra nulos
        $this->firstName = $user->name;
        $this->lastName = $user->last_name ?? '';
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
                // Para o mount() o cart já está hidratado
                $this->shippingOptions = $this->shippingService->calculate($address->zip_code, $this->cartItems);
            }
        }
    }

    /**
     * Helper Central: Puxa o carrinho fresco do banco com todas as relações
     * Essencial para evitar o LazyLoadingViolation após a desidratação do Livewire
     */
    public function loadCart()
    {
        $this->cartItems = CartItem::with(['product', 'variant'])
            ->where('user_id', Auth::id())
            ->get();

        if ($this->cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'Seu carrinho está vazio.');
        }
    }

    public function updatedSelectedAddressId($value)
    {
        if ($value) {
            $this->useNewAddress = false;
            $address = Address::find($value);
            if ($address) {
                $this->loadCart(); // HIDRATA O CARRINHO ANTES DE ENVIAR PARA O SERVIÇO
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
                $this->loadCart(); // HIDRATA O CARRINHO
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
                
                if (!$this->getErrorBag()->has('newAddress.zip_code')) {
                    $this->loadCart(); // HIDRATA O CARRINHO
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

    public function applyCoupon()
    {
        $this->resetErrorBag('couponCode');
        
        $code = strtoupper(trim($this->couponCode));

        if (empty($code)) {
            $this->addError('couponCode', 'Por favor, digite um código de cupom.');
            $this->calculateTotals(); 
            return;
        }

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            $this->discount = 0;
            $this->appliedCouponId = null;
            $this->couponDisplay = '';
            $this->addError('couponCode', 'Este cupom não existe. Verifique se digitou corretamente.');
            $this->calculateTotals();
            return;
        }

        // Reutiliza o loadCart para manter a Fonte Única da Verdade do carrinho
        $this->loadCart();

        $realSubtotal = 0;
        foreach ($this->cartItems as $item) {
            $realSubtotal += $item->total ?? ($item->quantity * ($item->variant ? $item->variant->price : $item->product->base_price));
        }

        $validation = $coupon->validateCoupon($realSubtotal);

        if (!$validation['valid']) {
            $this->discount = 0;
            $this->appliedCouponId = null;
            $this->couponDisplay = '';
            $this->addError('couponCode', $validation['message']);
        } else {
            $this->appliedCouponId = $coupon->id;
            $this->couponDisplay = $coupon->type === 'percentage' ? '(' . round($coupon->value) . '%)' : '';
            session()->flash('coupon_success', 'Cupom aplicado com sucesso!');
        }
        
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        // Ao chamar isso aqui, garantimos que qualquer mudança re-hidrate as relações para o Blade também
        $this->loadCart(); 

        $sub = 0;
        foreach ($this->cartItems as $item) {
            $sub += $item->total ?? ($item->quantity * ($item->variant ? $item->variant->price : $item->product->base_price));
        }
        $this->subtotal = $sub;

        if ($this->appliedCouponId) {
            $coupon = Coupon::find($this->appliedCouponId);
            $validation = $coupon ? $coupon->validateCoupon($this->subtotal) : ['valid' => false];
            
            if ($coupon && $validation['valid']) {
                $this->discount = $coupon->calculateDiscount($this->subtotal);
                $this->couponDisplay = $coupon->type === 'percentage' ? '(' . round($coupon->value) . '%)' : '';
            } else {
                $this->appliedCouponId = null;
                $this->discount = 0;
                $this->couponDisplay = '';
                $this->addError('couponCode', 'Cupom removido: ' . ($validation['message'] ?? 'Inválido para o carrinho atual.'));
            }
        } else {
            $this->discount = 0;
            $this->couponDisplay = '';
        }

        $this->total = ($this->subtotal + $this->shippingPrice) - $this->discount;
    }

    public function placeOrder()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            if ($this->appliedCouponId) {
                $coupon = Coupon::where('id', $this->appliedCouponId)->lockForUpdate()->first();
                $validation = $coupon ? $coupon->validateCoupon($this->subtotal) : ['valid' => false];
                
                if (!$coupon || !$validation['valid']) {
                    DB::rollBack();
                    $this->appliedCouponId = null;
                    $this->calculateTotals();
                    session()->flash('error', 'O cupom selecionado expirou ou esgotou seu limite enquanto você finalizava a compra. Revise o resumo e tente novamente.');
                    return;
                }
                
                $coupon->increment('used_count');
            }

            $address = null;
            if ($this->useNewAddress || Auth::user()->addresses->isEmpty()) {
                $address = Auth::user()->addresses()->create($this->newAddress);
            } else {
                $address = Address::find($this->selectedAddressId);
            }
            
            $order = Order::create([
                'user_id' => Auth::id(),
                'coupon_id' => $this->appliedCouponId,
                'status' => Order::STATUS_PENDING, 
                'total_amount' => $this->total,
                'shipping_cost' => $this->shippingPrice,
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
        // Proteção extra: Força a busca das relações no último milissegundo antes da tela ser desenhada
        $this->cartItems = CartItem::with(['product', 'variant'])
            ->where('user_id', Auth::id())
            ->get();

        return view('livewire.checkout-page')->layout('components.layout', ['title' => 'Checkout Segura']);
    }
}