@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>

<title>Craftmart</title>
<link>{{ url('/') }}</link>
<description>Product Feed</description>

@foreach($products as $product)
<item>

    <g:id>{{ $product->product_id ?? $product->id }}</g:id>

    <g:title><![CDATA[{{ $product->name ?? '' }}]]></g:title>

    <g:description><![CDATA[
        {{ strip_tags($product->description ?? '') }}
    ]]></g:description>

    <g:link>{{ url('/'.$product->url_key) }}</g:link>

<g:image_link>
{{ !empty($product->image) 
    ? url('cache/large/'.$product->image) 
    : 'https://via.placeholder.com/500' }}
</g:image_link>

    <g:availability>in stock</g:availability>

  <g:price>
{{ number_format((float)($product->price ?? 0), 2, '.', '') }} INR
</g:price>

@if(!empty($product->special_price))
<g:sale_price>
{{ number_format((float)$product->special_price, 2, '.', '') }} INR
</g:sale_price>
@endif

@if(!empty($product->special_price_from) && !empty($product->special_price_to))
<g:sale_price_effective_date>
{{ $product->special_price_from }}T00:00:00+05:30/{{ $product->special_price_to }}T23:59:59+05:30
</g:sale_price_effective_date>
@endif

<g:product_type><![CDATA[Santipur Handloom Saree]]></g:product_type>

<g:google_product_category><![CDATA[
Apparel & Accessories > Clothing > Sarees
]]></g:google_product_category>

    <g:condition>new</g:condition>

    <g:brand><![CDATA[{{ $product->brand ?? 'Generic' }}]]></g:brand>

</item>
@endforeach

</channel>
</rss>