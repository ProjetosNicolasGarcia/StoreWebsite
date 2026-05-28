<x-layout :title="$title ?? 'Catálogo'">
    @push('meta')
        <link rel="canonical" href="{{ url()->current() }}" />
    @endpush

    <livewire:product-listing 
        :category="isset($category) ? $category : null" 
        :collection="isset($collection) ? $collection : null" 
        :is-offers="isset($isOffers) ? $isOffers : false"
        :search="isset($searchQuery) ? $searchQuery : request()->query('q')" 
    />
</x-layout>