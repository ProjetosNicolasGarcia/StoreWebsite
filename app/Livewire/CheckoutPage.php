<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CartItem;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutPage extends Component
{
    // Totais
    public $cartItems = [];
    public $subtotal = 0;
    public $shippingPrice = 0;
    public $discount = 0;
    public $total = 0;

    // Campos do Formulário
    public $selectedAddressId = null;
    public $shippingMethod = 'pac';
    public $paymentMethod = 'credit_card';
    public $couponCode = '';
    
    // Dados Pessoais (Herdados do User/Profile)
    public $cpf;
    public $phone;
    public $fullName;

    // Novo Endereço
    public $useNewAddress = false;
    public $newAddress = [
        'zip_code' => '', 'street' => '', 'number' => '', 
        'complement' => '', 'neighborhood' => '', 'city' => '', 'state' => ''
    ];

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

    public function mount()
    {
        $user = Auth::user();
        $this->fullName = $user->name;
        $this->cpf = $user->cpf ?? ''; // Assumindo que o CPF pode estar na tabela users
        $this->phone = $user->phone ?? '';

        if ($user->addresses->isNotEmpty()) {
            $this->selectedAddressId = $user->addresses->first()->id;
        } else {
            $this->useNewAddress = true;
        }

        $this->loadCart();
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
        $this->subtotal = $this->cartItems->sum('total');
        
        // Mock de simulação de frete baseado no método
        $this->shippingPrice = $this->shippingMethod === 'sedex' ? 35.00 : 15.00;
        
        $this->total = ($this->subtotal + $this->shippingPrice) - $this->discount;
    }

    // Gatilho executado sempre que o método de envio ou endereço mudar
    public function updatedShippingMethod()
    {
        $this->calculateTotals();
    }

    public function updatedUseNewAddress($value)
    {
        if ($value) $this->selectedAddressId = null;
        $this->calculateTotals();
    }

    public function applyCoupon()
    {
        // Lógica simplificada de validação de cupom
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
            // 1. Resolve o Endereço de Entrega
            $address = null;
            if ($this->useNewAddress || Auth::user()->addresses->isEmpty()) {
                $address = Auth::user()->addresses()->create($this->newAddress);
            } else {
                $address = Address::find($this->selectedAddressId);
            }

            // 2. Cria o Pedido (Order)
            $order = Order::create([
                'user_id' => Auth::id(),
                'status' => Order::STATUS_PENDING,
                'total_price' => $this->total,
                'shipping_price' => $this->shippingPrice,
                'discount' => $this->discount,
                'payment_method' => $this->paymentMethod,
                'address_json' => $address->toArray(), // Snapshot do endereço no momento da compra
            ]);

            // 3. Move os Itens do Carrinho para o Pedido
            foreach ($this->cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->total / $item->quantity,
                ]);
            }

            // 4. Limpa o Carrinho
            CartItem::where('user_id', Auth::id())->delete();

            // 5. Integração com Gateway de Pagamento (Ponto de Extensão)
            // Aqui você chamaria o seu PaymentService:
            // $paymentUrl = app(PaymentService::class)->process($order);

            DB::commit();

            // Redireciona para página de sucesso ou pro gateway
            return redirect()->route('profile.orders')->with('status', 'Pedido realizado com sucesso! Aguardando pagamento.');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Ocorreu um erro ao processar seu pedido. Tente novamente.');
            // Log::error($e->getMessage());
        }
    }

    public function render()
    {
        // Usa o layout principal do seu site
        return view('livewire.checkout-page')->layout('components.layout', ['title' => 'Checkout Segura']);
    }
}