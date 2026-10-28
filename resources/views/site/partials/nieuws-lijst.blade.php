@php use App\Support\DutchTime; @endphp
@if ($posts->isEmpty() && $releases->isEmpty())
    <div class="jury-slot" style="max-width:640px"><p class="body-text">Er zijn nog geen berichten. Hier verschijnen de bekendmaking van de eerste deelnemers, de start van de beoordelingen{{ $startLabel ? ' op '.$startLabel : '' }} en de uitslagen.</p></div>
@else
    <div class="stagger-grid" style="display:grid;gap:18px;max-width:760px">
        @foreach ($posts as $post)
            <article class="jury-slot rev">
                <span class="eyebrow">{{ DutchTime::format($post->published_at, 'D MMMM YYYY') }}</span>
                <h3 style="margin:6px 0 8px"><a href="{{ route('nieuws.toon', $post) }}" style="color:inherit;text-decoration:none">{{ $post->title }}</a></h3>
                @if ($post->excerpt)<p class="body-text">{{ $post->excerpt }}</p>@endif
                <p style="margin-top:10px"><a href="{{ route('nieuws.toon', $post) }}" style="color:var(--gold);font-weight:700">Lees verder &rarr;</a></p>
            </article>
        @endforeach
        @foreach ($releases as $release)
            <article class="jury-slot rev">
                <span class="eyebrow">Persbericht{{ $release->province ? ' · '.$release->province->name : '' }} · {{ DutchTime::format($release->published_at, 'D MMMM YYYY') }}</span>
                <h3 style="margin:6px 0 8px"><a href="{{ route('pers.toon', $release) }}" style="color:inherit;text-decoration:none">{{ $release->title }}</a></h3>
                <p style="margin-top:10px"><a href="{{ route('pers.toon', $release) }}" style="color:var(--gold);font-weight:700">Lees het persbericht &rarr;</a></p>
            </article>
        @endforeach
    </div>
@endif
