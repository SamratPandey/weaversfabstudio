@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp

<rss version="2.0"
xmlns:media="http://search.yahoo.com/mrss/">

<channel>

<title>Craftmart Pinterest Feed</title>

<link>{{ url('/') }}</link>

<description>
Santipur Handloom Saree Collection
</description>

@foreach($products as $product)

<item>

<title><![CDATA[
{{ $product->name ?? '' }}
]]></title>

<link>
{{ url('/'.$product->url_key) }}
</link>

<description><![CDATA[
{{ strip_tags($product->short_description ?? '') }}
]]></description>

<media:content
url="{{ url('cache/large/'.$product->image) }}"
medium="image" />

</item>

@endforeach

</channel>
</rss>