<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\CartItem;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ShippingService; 
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CheckoutPage extends Component
{
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

    public $cardToken;
    public $installments = 1;
    public $cardPaymentMethodId;
    public $cardIssuerId;

    public $deviceId;

    protected ShippingService $shippingService;
    protected PaymentService $paymentService;

    public function boot(ShippingService $shippingService, PaymentService $paymentService)
    {
        $this->shippingService = $shippingService;
        $this->paymentService = $paymentService;
    }

    #[Computed]
    public function cartItems()
    {
        $items = CartItem::with(['product.variants', 'variant'])
            ->where('user_id', Auth::id())
            ->get();
            
        if ($items->isEmpty()) {
            redirect()->route('cart.index')->with('status', 'Seu carrinho está vazio.');
        }
        
        return $items;
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
        $user = Auth::user()->fresh(['addresses']);

        $this->firstName = $user->name;
        $this->lastName = $user->last_name ?? '';
        $this->cpf = $user->cpf ?? ''; 
        $this->phone = $user->phone ?? '';

        if ($user->addresses->isNotEmpty()) {
            $this->selectedAddressId = $user->addresses->first()->id;
        } else {
            $this->useNewAddress = true;
        }

        $this->calculateTotals();
    }

    public function loadInitialShipping()
    {
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
        $currentSubtotal = 0;
        $fullPriceSubtotal = 0;
        $now = now(); 

        foreach ($this->cartItems as $item) {
            $base = (float) ($item->variant ? $item->variant->price : $item->product->base_price);
            $unit = $base;
            
            if ($item->variant && !is_null($item->variant->sale_price) && (float)$item->variant->sale_price > 0 && (float)$item->variant->sale_price < $base) {
                $start = $item->variant->sale_start_date;
                $end = $item->variant->sale_end_date;
                
                if ((!$start || \Carbon\Carbon::parse($start)->lte($now)) && (!$end || \Carbon\Carbon::parse($end)->gte($now))) {
                    $unit = (float) $item->variant->sale_price;
                }
            } 
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

    private function translateMPError($errorMessage)
    {
        $errorLower = strtolower($errorMessage);

        $dictionary = [
            'security_code_length' => 'Verifique o número de dígitos do código de segurança (CVV).',
            'invalid_security_code' => 'O código de segurança (CVV) informado é inválido.',
            'invalid_expiration_date' => 'A data de validade do cartão é inválida.',
            'invalid_card_number' => 'O número do cartão de crédito é inválido.',
            'not_result_by_params' => 'Bandeira do cartão ou parcelamento indisponível. Verifique os dados.',
            'falha ao gerar o pix' => 'Ocorreu um erro interno ao gerar o PIX. Tente novamente em instantes.',
            'falha ao gerar o boleto' => 'Ocorreu um erro interno ao gerar o Boleto. Tente novamente em instantes.',
        ];

        foreach ($dictionary as $key => $translation) {
            if (str_contains($errorLower, $key)) {
                return $translation;
            }
        }

        return $errorMessage;
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

            // TRAVAMENTO PESSIMISTA E RESERVA DE ESTOQUE IMEDIATA
            foreach ($this->cartItems as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                
                $variant = null;
                if ($item->product_variant_id) {
                    $variant = ProductVariant::where('id', $item->product_variant_id)->lockForUpdate()->first();
                    $stockAvailable = $variant->quantity;
                } else {
                    $stockAvailable = $product->quantity;
                }

                if ($stockAvailable < $item->quantity) {
                    throw new \Exception("O produto '{$product->name}' esgotou ou não possui a quantidade solicitada em estoque.");
                }

                // Deduz o estoque para reservar a unidade e impedir Overselling
                if ($variant) {
                    $variant->decrement('quantity', $item->quantity);
                } else {
                    $product->decrement('quantity', $item->quantity);
                }
            }

            $address = null;
            if ($this->useNewAddress || Auth::user()->addresses->isEmpty()) {
                $address = Auth::user()->addresses()->create($this->newAddress);
            } else {
                $address = Address::where('id', $this->selectedAddressId)
                                  ->where('user_id', Auth::id())
                                  ->firstOrFail();
            }
            
            $shippingMethodName = 'Desconhecido';
            if ($this->shippingMethod && is_array($this->shippingOptions)) {
                foreach ($this->shippingOptions as $option) {
                    if ($option['id'] == $this->shippingMethod) {
                        $shippingMethodName = $option['name'];
                        break;
                    }
                }
            }
            
            // 🛠️ ATUALIZADO: MAPEAMENTO DAS NOVAS COLUNAS DE DESCONTO AQUI
            $order = Order::create([
                'user_id' => Auth::id(),
                'coupon_id' => $this->appliedCouponId,
                'status' => Order::STATUS_PENDING, 
                'total_amount' => $this->total,   
                'shipping_cost' => $this->shippingPrice,
                'shipping_method' => $shippingMethodName, 
                'discount' => ($this->discount + $this->offerSavings), // Mantém soma genérica caso Precise
                'promotional_discount' => $this->offerSavings,         // Desconto de oferta ($offerSavings) gravado no BD
                'coupon_discount' => $this->discount,                  // Desconto de cupom ($this->discount) gravado no BD
                'payment_method' => $this->paymentMethod, 
                'address_json' => $address ? $address->toArray() : [], 

                'customer_first_name' => $this->firstName,
                'customer_last_name' => $this->lastName,
                'customer_cpf' => preg_replace('/\D/', '', $this->cpf),
                'customer_phone' => preg_replace('/\D/', '', $this->phone),
            ]);

            foreach ($this->cartItems as $item) {
                $base = (float) ($item->variant ? $item->variant->price : $item->product->base_price);
                $unitPrice = $base;
                $now = now();
            
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

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product->name, 
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                ]);
            }

            if ($this->paymentMethod === 'pix') {
                $paymentResult = $this->paymentService->createPixPayment(
                    $order, $this->cpf, $this->firstName, $this->lastName, Auth::user()->email
                );

                if (!$paymentResult['success']) {
                    throw new \Exception('Falha ao gerar o PIX: ' . $paymentResult['message']);
                }

                $order->update([
                    'payment_id' => $paymentResult['payment_id'],
                    'pix_qr_code' => $paymentResult['qr_code'],
                    'pix_qr_code_base64' => $paymentResult['qr_code_base64'],
                ]);
            } elseif ($this->paymentMethod === 'boleto') {
                $paymentResult = $this->paymentService->createBoletoPayment(
                    $order, $this->cpf, $this->firstName, $this->lastName, Auth::user()->email, $address->toArray()
                );

                if (!$paymentResult['success']) {
                    throw new \Exception('Falha ao gerar o Boleto: ' . $paymentResult['message']);
                }

                $order->update([
                    'payment_id' => $paymentResult['payment_id'],
                    'boleto_url' => $paymentResult['boleto_url'],
                ]);
            } elseif ($this->paymentMethod === 'credit_card') {
                
                if (empty($this->cardToken)) {
                    throw new \Exception("Os dados do cartão de crédito não puderam ser verificados ou a tokenização falhou.");
                }

                $paymentResult = $this->paymentService->createCreditCardPayment(
                    $order, 
                    $this->cpf, 
                    $this->firstName, 
                    $this->lastName, 
                    Auth::user()->email, 
                    $this->cardToken,
                    (int) $this->installments, 
                    $this->cardPaymentMethodId, 
                    $this->cardIssuerId,
                    $this->deviceId
                );

                if (!$paymentResult['success']) {
                    $this->cardToken = null;
                    throw new \Exception($paymentResult['message']);
                }

                $order->update([
                    'payment_id' => $paymentResult['payment_id'],
                    'status'     => ($paymentResult['status'] === 'approved') ? Order::STATUS_PAID : Order::STATUS_PENDING,
                ]);
            }

            CartItem::where('user_id', Auth::id())->delete();
            
            DB::commit(); // Commita as transações e libera as linhas do banco de dados

            // DISPARO DA FILA DE DEVOLUÇÃO DE ESTOQUE (TTL)
            if ($this->paymentMethod === 'pix') {
                if (class_exists(\App\Jobs\ReleaseUnpaidStock::class)) {
                    \App\Jobs\ReleaseUnpaidStock::dispatch($order->id)->delay(now()->addSeconds(30));
                }
            } elseif ($this->paymentMethod === 'boleto') {
                if (class_exists(\App\Jobs\ReleaseUnpaidStock::class)) {
                    \App\Jobs\ReleaseUnpaidStock::dispatch($order->id)->delay(now()->addDays(3));
                }
            }

            // Simulação de E-mail
            $itensDoPedido = "";
            foreach ($this->cartItems as $item) {
                $nomeItem = $item->product->name ?? 'Produto Indisponível';
                
                $detalhesVariante = "";
                if ($item->variant && is_array($item->variant->options)) {
                    $opcoes = [];
                    foreach ($item->variant->options as $key => $value) {
                        $opcoes[] = "{$key}: {$value}";
                    }
                    if (count($opcoes) > 0) {
                        $detalhesVariante = " (" . implode(", ", $opcoes) . ")";
                    }
                }
                
                $itensDoPedido .= "   - {$item->quantity}x {$nomeItem}{$detalhesVariante}\n";
            }

            $enderecoFormatado = "Endereço não disponível.";
            if ($address) {
                $complemento = !empty($address->complement) ? " - " . $address->complement : "";
                $enderecoFormatado = "{$address->street}, {$address->number}{$complemento}\n            Bairro: {$address->neighborhood}\n            {$address->city} - {$address->state}\n            CEP: {$address->zip_code}";
            }

            $emailSimulado = "
            ====================================================================
            📧 SIMULAÇÃO DE DISPARO DE E-MAIL DE CONFIRMAÇÃO 📧
            ====================================================================
            PARA: " . Auth::user()->email . "
            ASSUNTO: Pedido Recebido #" . str_pad($order->id, 6, '0', STR_PAD_LEFT) . " - Minha Loja
            --------------------------------------------------------------------
            Olá, {$this->firstName}! 
            
            Recebemos o seu pedido e ele já está sendo processado.
            
            🛍️ PRODUTOS ADQUIRIDOS:
            {$itensDoPedido}
            
            📍 LOCAL DE ENTREGA:
            {$enderecoFormatado}
            
            📦 RESUMO FINANCEIRO:
            Método de Pagamento: " . strtoupper($this->paymentMethod) . "
            Método de Entrega: {$shippingMethodName}
            Custo do Frete: R$ " . number_format($this->shippingPrice, 2, ',', '.') . "
            Total Pago: R$ " . number_format($this->total, 2, ',', '.') . "
            
            Agradecemos a preferência!
            Equipe Minha Loja
            ====================================================================
            ";

            \Illuminate\Support\Facades\Log::info($emailSimulado);

            $this->dispatch('cart-updated');

            return redirect()->route('checkout.success', ['order' => $order->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $mensagemDeErro = $e->getMessage();
            \Illuminate\Support\Facades\Log::error('FALHA_PLACE_ORDER', [
                'motivo' => $mensagemDeErro,
                'arquivo' => $e->getFile(),
                'linha' => $e->getLine()
            ]);
            
            if (method_exists($this, 'translateMPError')) {
                try {
                    $mensagemAmigavel = $this->translateMPError($mensagemDeErro);
                } catch (\Throwable $th) {
                    $mensagemAmigavel = 'Ocorreu um erro na requisição. Verifique seus dados.';
                }
            } else {
                $mensagemAmigavel = $mensagemDeErro;
            }

            session()->flash('error', $mensagemAmigavel);
        }
    }

    public function render()
    {
        return view('livewire.checkout-page')->layout('components.layout', ['title' => 'Checkout Seguro']);
    }

    public function injectFrontEndError($message)
    {
        session()->flash('error', $this->translateMPError($message));
    }
}