@foreach($batch_export as $batch)

{{ $batch->implode(",") }}

@endforeach
