{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
{{-- Ligne 1 : écrite via {!! !!} car "<?" serait pris pour une balise PHP ; elle doit être tout en haut du fichier --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ([route('home'), route('legal.notice'), route('legal.privacy')] as $url)
        <url>
            <loc>{{ $url }}</loc>
        </url>
    @endforeach
</urlset>
