{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
@foreach ($tenants as $tenant)
    <url>
        <loc>{{ url('/' . $tenant->slug) }}</loc>
@if ($tenant->updated_at)
        <lastmod>{{ $tenant->updated_at->toAtomString() }}</lastmod>
@endif
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
</urlset>
