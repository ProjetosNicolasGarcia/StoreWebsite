<?php
namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMail;

class CreateTicket extends Component
{
    public $order_id;
    public $subject;
    public $message;
    public $name;
    public $email;

    protected function rules()
    {
        if (Auth::check()) {
            return [
                'subject' => 'required|string|min:5|max:255',
                'message' => 'required|string|min:15',
                'order_id' => 'nullable|exists:orders,id',
            ];
        }

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|min:5|max:255',
            'message' => 'required|string|min:15',
        ];
    }

    protected function messages()
    {
        return [
            'name.required' => 'Por favor, informe seu nome.',
            'email.required' => 'O e-mail é necessário para retornarmos seu contato.',
            'email.email' => 'Insira um e-mail válido.',
            'subject.required' => 'O assunto é obrigatório.',
            'subject.min' => 'O assunto deve ter pelo menos 5 caracteres.',
            'message.required' => 'Por favor, escreva sua dúvida ou relato.',
            'message.min' => 'Sua mensagem está muito curta. Escreva pelo menos 15 caracteres para podermos ajudar.',
        ];
    }

    public function mount()
    {
        $this->order_id = request()->query('order');
    }

    public function submitSupport()
    {
        $this->validate();

        try {
            // Determina a origem do nome e e-mail baseado na autenticação
            $contactName = Auth::check() ? Auth::user()->name : $this->name;
            $contactEmail = Auth::check() ? Auth::user()->email : $this->email;

            // Monta a carga de dados para o Mailable
            $mailData = [
                'name' => $contactName,
                'email' => $contactEmail,
                'subject' => $this->subject,
                'message' => $this->message,
                'order_id' => $this->order_id,
            ];

            // Despacha para a fila
            Mail::to(config('mail.from.address'))->queue(new ContactMail($mailData));
            
            session()->flash('success', 'Mensagem enviada com sucesso! Nossa equipe retornará para o e-mail ' . $contactEmail . ' em breve.');

            $this->reset(['subject', 'message', 'name', 'email']);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente mais tarde.');
        }
    }

    public function render()
    {
        return view('livewire.create-ticket')->layout('components.layout');
    }
}