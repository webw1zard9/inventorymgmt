<div>
    <!-- Let all your things have their places; let each part of your business have its time. - Benjamin Franklin -->

{{--    @foreach($price_ranges->groupBy('uom') as $uom=>$batches)--}}
{{--        {{ $uom }}: {{ $batches->sum('batches_count') }}<br>--}}
{{--    @endforeach--}}

    @dump($price_ranges)
</div>