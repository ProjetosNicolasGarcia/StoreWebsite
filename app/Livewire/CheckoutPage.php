<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CartItem;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Services\ShippingService; 
use App\Services\PaymentService;
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

    public $offerSavings = 0;

    public $useNewAddress = false;
    public $newAddress = [
        'zip_code' => '', 'street' => '', 'number' => '', 
        'complement' => '', 'neighborhood' => '', 'city' => '', 'state' => ''
    ];

    protected ShippingService $shippingService;
    protected PaymentService $paymentService;

    public function boot(ShippingService $shippingService, PaymentService $paymentService)
    {
        $this->shippingService = $shippingService;
        $this->paymentService = $paymentService;
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
        $user = Auth::user()->fresh();

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
                $this->shippingOptions = $this->shippingService->calculate($address->zip_code, $this->cartItems);
            }
        }
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

    public function updatedSelectedAddressId($value)
    {
        if ($value) {
            $this->useNewAddress = false;
            $address = Address::find($value);
            if ($address) {
                $this->loadCart();
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
                $this->loadCart(); 
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
                    $this->loadCart();
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
        $this->loadCart(); 

        $currentSubtotal = 0;
        $fullPriceSubtotal = 0;
        $now = now(); // Puxa a data e hora atual do sistema

        foreach ($this->cartItems as $item) {
            $base = (float) ($item->variant ? $item->variant->price : $item->product->base_price);
            $unit = $base;
            
            // VERIFICAÇÃO RIGOROSA DE DATAS PARA A VARIANTE
            if ($item->variant && !is_null($item->variant->sale_price) && (float)$item->variant->sale_price > 0 && (float)$item->variant->sale_price < $base) {
                $start = $item->variant->sale_start_date;
                $end = $item->variant->sale_end_date;
                
                // Só aplica se não tiver data, ou se a data atual estiver dentro do prazo
                if ((!$start || \Carbon\Carbon::parse($start)->lte($now)) && (!$end || \Carbon\Carbon::parse($end)->gte($now))) {
                    $unit = (float) $item->variant->sale_price;
                }
            } 
            // VERIFICAÇÃO RIGOROSA DE DATAS PARA O PRODUTO BASE
            elseif (!is_null($item->product->sale_price) && (float)$item->product->sale_price > 0 && (float)$item->product->sale_price < $base) {
                $start = $item->product->sale_start_date;
                $end = $item->product->sale_end_date;
                
                if ((!$start || \Carbon\Carbon::parse($start)->lte($now)) && (!$end || \Carbon\Carbon::parse($end)->gte($now))) {
                    $unit = (float) $item->product->sale_price;
                }
            }

            $currentSubtotal += ($unit * $item->quantity);
            $fullPriceSubtotal += ($base * $item->quantity);
        }

        $this->subtotal = round($currentSubtotal, 2);
        
        $savings = round($fullPriceSubtotal - $currentSubtotal, 2);
        $this->offerSavings = $savings > 0 ? $savings : 0;

        if ($this->appliedCouponId) {
            $coupon = Coupon::find($this->appliedCouponId);
            $this->discount = $coupon ? $coupon->calculateDiscount($this->subtotal) : 0;
        }

        $this->total = round(($this->subtotal + $this->shippingPrice) - $this->discount, 2);
    }

   public function placeOrder()
    {
        $this->validate();
        $this->loadCart();

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
            
            // Puxa o nome legível da transportadora em vez do ID
            $shippingMethodName = 'Desconhecido';
            if ($this->shippingMethod && is_array($this->shippingOptions)) {
                foreach ($this->shippingOptions as $option) {
                    if ($option['id'] == $this->shippingMethod) {
                        $shippingMethodName = $option['name'];
                        break;
                    }
                }
            }
            
            $order = Order::create([
                'user_id' => Auth::id(),
                'coupon_id' => $this->appliedCouponId,
                'status' => Order::STATUS_PENDING, 
                'total_amount' => $this->total,   
                'shipping_cost' => $this->shippingPrice,
                'shipping_method' => $shippingMethodName, 
                'discount' => $this->discount,    
                'payment_method' => $this->paymentMethod, 
                'address_json' => $address ? $address->toArray() : [], 
            ]);

            foreach ($this->cartItems as $item) {
                            $base = (float) ($item->variant ? $item->variant->price : $item->product->base_price);
                            $unitPrice = $base;
                            $now = now();
                        
                            // Valida as datas antes de salvar o valor no banco
                            if ($item->variant && !is_null($item->variant->sale_price) && (float)$item->variant->sale_price > 0 && (float)$item->variant->sale_price < $base) {
                                $start = $item->variant->sale_start_date;
                                $end = $item->variant->sale_end_date;
                                if ((!$start || \Carbon\Carbon::parse($start)->lte($now)) && (!$end || \Carbon\Carbon::parse($end)->gte($now))) {
                                    $unitPrice = (float) $item->variant->sale_price;
                                }
                            } elseif (!is_null($item->product->sale_price) && (float)$item->product->sale_price > 0 && (float)$item->product->sale_price < $base) {
                                $start = $item->product->sale_start_date;
                                $end = $item->product->sale_end_date;
                                if ((!$start || \Carbon\Carbon::parse($start)->lte($now)) && (!$end || \Carbon\Carbon::parse($end)->gte($now))) {
                                    $unitPrice = (float) $item->product->sale_price;
                                }
                            }

                            $productName = $item->product->name;

                            OrderItem::create([
                                'order_id' => $order->id,
                                'product_id' => $item->product_id,
                                'product_variant_id' => $item->product_variant_id,
                                'product_name' => $productName, 
                                'quantity' => $item->quantity,
                                'unit_price' => $unitPrice,
                            ]);
                        }

                        if ($this->paymentMethod === 'pix') {
                            $paymentResult = $this->paymentService->createPixPayment(
                                $order,
                                $this->cpf,
                                $this->firstName,
                                $this->lastName,
                                Auth::user()->email
                            );

                            if (!$paymentResult['success']) {
                                // Lança exceção para acionar o DB::rollBack() e cancelar a transação no banco
                                throw new \Exception('Falha ao gerar o PIX: ' . $paymentResult['message']);
                            }

                            // Atualiza o pedido com os dados recebidos do MP
                            $order->update([
                                'payment_id' => $paymentResult['payment_id'],
                                'pix_qr_code' => $paymentResult['qr_code'],
                                'pix_qr_code_base64' => $paymentResult['qr_code_base64'],
                            ]);
                        }  elseif ($this->paymentMethod === 'boleto') {
                            // Convertendo a model Address para array para passar ao serviço
                            $paymentResult = $this->paymentService->createBoletoPayment(
                                $order,
                                $this->cpf,
                                $this->firstName,
                                $this->lastName,
                                Auth::user()->email,
                                $address->toArray() // Passando os dados do endereço
                            );

                            if (!$paymentResult['success']) {
                                // Dispara a exceção que faz o rollback da transação de banco de dados
                                throw new \Exception('Falha ao gerar o Boleto: ' . $paymentResult['message']);
                            }

                            $order->update([
                                'payment_id' => $paymentResult['payment_id'],
                                'boleto_url' => $paymentResult['boleto_url'],
                            ]);
                        }

            CartItem::where('user_id', Auth::id())->delete();
            DB::commit();

            // DISPARO DE EVENTO PARA O ALPINE/LIVEWIRE: Limpa contadores globais de carrinho.
            $this->dispatch('cart-updated');

            return redirect()->route('checkout.success', ['order' => $order->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Falha no Checkout: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            session()->flash('error', 'Ocorreu um erro ao processar seu pedido. Tente novamente.');
        }
    }

    public function render()
    {
        $this->cartItems = CartItem::with(['product', 'variant'])
            ->where('user_id', Auth::id())
            ->get();

        return view('livewire.checkout-page')->layout('components.layout', ['title' => 'Checkout Segura']);
    }
}