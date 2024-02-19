@php use App\CategoryPriceRange; @endphp
<div>
    {{-- Care about people's approval and you will be their prisoner. --}}

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                <h1><small>Category:</small> {{ $category->name }}</h1>

                <h4>Create</h4>
                    @livewire('categories.price-ranges.item', [
                                'category' => $category,
                                'category_price_range' => new CategoryPriceRange()
                            ], key('new-item'))

                @if($category->price_ranges->count())
                <hr />

                <h4>Existing</h4>
                @foreach($category->price_ranges as $category_price_range)

                    @livewire('categories.price-ranges.item', [
                        'category' => $category,
                        'category_price_range' => $category_price_range
                    ], key($category_price_range->id))

                @endforeach
                @endif

            </div>

        </div>
</div>
