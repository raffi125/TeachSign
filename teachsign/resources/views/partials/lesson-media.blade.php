@php($media = \App\Support\MediaEmbed::parse($lesson->media_url ?? null))

@if ($media['type'] === 'youtube' || $media['type'] === 'drive')
    <div class="media-embed">
        <iframe src="{{ $media['embed'] }}" title="Media gestur {{ $lesson->title }}"
                loading="lazy" allowfullscreen
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>
@elseif ($media['type'] === 'video')
    <video class="lesson-media" controls preload="metadata" src="{{ $lesson->media_url }}"></video>
@elseif ($media['type'] === 'image')
    <img class="lesson-media" src="{{ $lesson->media_url }}" alt="Media gestur {{ $lesson->title }}">
@elseif ($media['type'] === 'link')
    <a href="{{ $lesson->media_url }}" target="_blank" rel="noopener" class="media-link">
        Buka media gestur ↗
    </a>
@endif
