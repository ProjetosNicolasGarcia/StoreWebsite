<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Product;
use App\Models\Category;
use App\Models\Collection;

class ProductListing extends Component
{
    use WithPagination;

    public ?Category $category = null;
    public ?Collection $collection = null; 
    public bool $isOffers = false;         
    public ?string $search = null;

    // ✅ novo: Variável única e à prova de falhas para agregar todos os checkboxes selecionados
    #[Url(history: true, keep: false)]
    public array $selectedFilters = [];

    #[Url(history: true, keep: false)]
    public ?float $minPrice = null;

    #[Url(history: true, keep: false)]
    public ?float $maxPrice = null;

    public function updated($property)
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['selectedFilters', 'minPrice', 'maxPrice']); // ✅ alterado
        $this->resetPage();
    }

    public function render()
    {
        $query = Product::query()
            ->where('is_active', 1)
            ->with(['variants', 'categories']);

        if ($this->category) {
            $query->whereHas('categories', function ($q) {
                $q->where('categories.id', $this->category->id);
            });
        }

        if ($this->collection) {
            $query->whereHas('collections', function ($q) {
                $q->where('collections.id', $this->collection->id);
            });
        }

        if ($this->isOffers) {
            $query->whereHas('variants', function ($q) {
                $q->whereNotNull('sale_price')
                  ->where('sale_start_date', '<=', now())
                  ->where(function ($sub) {
                      $sub->whereNull('sale_end_date')
                          ->orWhere('sale_end_date', '>=', now());
                  });
            });
        }

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $availableFilters = $this->extractDynamicFilters(clone $query);
        $this->applyDynamicFilters($query);

        $userFavoriteIds = auth()->check() ? auth()->user()->favorites()->pluck('product_id')->toArray() : [];

        return view('livewire.product-listing', [
            'products' => $query->paginate(12),
            'availableCharacteristics' => $availableFilters['characteristics'],
            'availableOptions' => $availableFilters['options'],
            'userFavoriteIds' => $userFavoriteIds,
        ]);
    }

    private function applyDynamicFilters($query): void
    {
        $activeChars = [];
        $activeOpts = [];

        // ✅ novo: Desmembra a string segura que vem do checkbox e agrupa as chaves
        foreach ($this->selectedFilters as $filter) {
            $parts = explode(':::', $filter, 3);
            if (count($parts) === 3) {
                if ($parts[0] === 'char') $activeChars[$parts[1]][] = $parts[2];
                if ($parts[0] === 'opt') $activeOpts[$parts[1]][] = $parts[2];
            }
        }

        foreach ($activeChars as $key => $values) {
            $query->where(function ($q) use ($key, $values) {
                foreach ($values as $value) {
                    $q->orWhereJsonContains('characteristics->'.$key, $value);
                }
            });
        }

        foreach ($activeOpts as $key => $values) {
            $query->whereHas('variants', function ($q) use ($key, $values) {
                $q->where(function ($subQ) use ($key, $values) {
                    foreach ($values as $value) {
                        $subQ->orWhereJsonContains('options->'.$key, $value);
                    }
                });
            });
        }

        if ($this->minPrice) {
             $query->whereHas('variants', fn($q) => $q->where('price', '>=', $this->minPrice));
        }
        
        if ($this->maxPrice) {
             $query->whereHas('variants', fn($q) => $q->where('price', '<=', $this->maxPrice));
        }
    }

    private function extractDynamicFilters($baseQuery): array
    {
        $products = $baseQuery->limit(500)->get(); 
        
        $characteristics = [];
        $options = [];

        // ✅ novo: Adicionado trim() massivo e conversões de tipo para acabar com as categorias repetidas
        foreach ($products as $prod) {
            $chars = is_string($prod->characteristics) ? json_decode($prod->characteristics, true) : $prod->characteristics;
            if (is_array($chars)) {
                foreach ($chars as $k => $v) {
                    $k = trim($k); 
                    if ($k === '') continue;
                    if (!isset($characteristics[$k])) $characteristics[$k] = [];
                    
                    if (is_array($v)) {
                        foreach ($v as $subV) {
                            $subV = trim((string)$subV);
                            if ($subV !== '' && !in_array($subV, $characteristics[$k])) {
                                $characteristics[$k][] = $subV;
                            }
                        }
                    } else {
                        $v = trim((string)$v);
                        if ($v !== '' && !in_array($v, $characteristics[$k])) {
                            $characteristics[$k][] = $v;
                        }
                    }
                }
            }

            foreach ($prod->variants as $variant) {
                $opts = is_string($variant->options) ? json_decode($variant->options, true) : $variant->options;
                if (is_array($opts)) {
                    foreach ($opts as $k => $v) {
                        $k = trim($k);
                        if ($k === '') continue;
                        if (!isset($options[$k])) $options[$k] = [];
                        
                        if (is_array($v)) {
                            foreach ($v as $subV) {
                                $subV = trim((string)$subV);
                                if ($subV !== '' && !in_array($subV, $options[$k])) {
                                    $options[$k][] = $subV;
                                }
                            }
                        } else {
                            $v = trim((string)$v);
                            if ($v !== '' && !in_array($v, $options[$k])) {
                                $options[$k][] = $v;
                            }
                        }
                    }
                }
            }
        }

        foreach ($characteristics as &$val) sort($val);
        foreach ($options as &$val) sort($val);

        ksort($characteristics);
        ksort($options);

        return ['characteristics' => $characteristics, 'options' => $options];
    }
}