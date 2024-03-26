<section class="all-events-cards">
    @foreach ($events as $event)
        <div class="event-card col">
            @if ($event->images->isNotEmpty())
                <div class="event-image" style="background-image: url('{{ \App\Http\Controllers\FileController::get('event_image', $event->images->first()->event_image_id) }}');"></div>
            @else
                <div class="event-image" style="background-image: url('{{ asset('media/event_image.jpg') }}');"></div>
            @endif
            <a href="{{ route('view-event', ['id' => $event->event_id]) }}" class="event-info">
                <p class="event-card-local">{{ $event->location }}</p>
                <p class="event-card-name">{{ $event->name }}</p>
                <p class="event-card-date">{!! $event->start_timestamp->format('H:i, F j, Y') !!}<br></p>
            </a>
        </div>
    @endforeach
</section>

<section class="mt-4"></section>

<section class="pagination mt-4 justify-content-center" id="pagination-links">
    {{ $events->links() }}
</section>
