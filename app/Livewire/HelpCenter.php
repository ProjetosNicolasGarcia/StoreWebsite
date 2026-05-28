<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\FaqCategory;

class HelpCenter extends Component
{
    public $search = '';
    public $order_id;

    public function mount()
    {
        $this->order_id = request()->query('order');
    }

    public function render()
    {
        $categories = FaqCategory::with(['faqs' => function($query) {
            if ($this->search) {
                $query->where('question', 'like', '%' . $this->search . '%')
                      ->orWhere('answer', 'like', '%' . $this->search . '%');
            }
            $query->where('is_active', true);
        }])
        ->orderBy('sort_order')
        ->get()
        ->filter(function ($category) {
            return $category->faqs->isNotEmpty();
        });

        return view('livewire.help-center', [
            'categories' => $categories,
        ])->layout('components.layout');
    }
}