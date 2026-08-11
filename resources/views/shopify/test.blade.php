@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6 space-y-6">
    <div>
        <h1 class="text-3xl font-bold">Shopify credential check</h1>
        <p class="mt-2 text-gray-600">Validate a Shopify client-credentials app, then run small read-only catalog checks.</p>
    </div>

    @if (session('status')) <div class="border border-green-200 bg-green-50 p-4 text-green-800">{{ session('status') }}</div> @endif
    @if ($errors->any()) <div class="border border-red-200 bg-red-50 p-4 text-red-800">Check the highlighted credential fields and try again.</div> @endif
    @if ($error) <div class="border border-red-200 bg-red-50 p-4 text-red-800"><strong>Not validated:</strong> {{ $error }}</div> @endif
    @if ($result) <div class="border border-green-200 bg-green-50 p-4 text-green-800"><strong>Success:</strong> {{ $result['message'] }}@if($result['shop'] ?? null) Shop: {{ $result['shop'] }}.@endif</div> @endif

    <form method="POST" action="{{ route('shopify.test.run') }}" class="bg-white border p-6 grid md:grid-cols-2 gap-4">
        @csrf
        <label class="block md:col-span-2">Shop domain
            <input name="shop_url" value="{{ old('shop_url', $saved['shop_url'] ?? '') }}" placeholder="your-store.myshopify.com" required class="mt-1 w-full border rounded p-2">
        </label>
        <label class="block">Client ID
            <input name="client_id" value="{{ old('client_id', $saved['client_id'] ?? '') }}" required autocomplete="off" class="mt-1 w-full border rounded p-2">
        </label>
        <label class="block">Client secret
            <input type="password" name="client_secret" value="" placeholder="{{ $saved ? 'Enter again to revalidate' : '' }}" required autocomplete="new-password" class="mt-1 w-full border rounded p-2">
        </label>
        <label class="block md:col-span-2">Scopes
            <input name="scope" value="{{ old('scope', $saved['scope'] ?? 'read_products,read_locations,read_inventory') }}" required class="mt-1 w-full border rounded p-2">
        </label>
        <label class="block">API version
            <input name="api_version" value="{{ old('api_version', $saved['api_version'] ?? '2026-07') }}" required class="mt-1 w-full border rounded p-2">
        </label>
        <div class="md:col-span-2"><button class="bg-blue-700 text-white rounded px-4 py-2">Validate and keep for this session</button></div>
    </form>

    @if ($saved)
    <div class="border bg-white p-6">
        <h2 class="text-xl font-semibold">Read-only checks</h2>
        <p class="text-sm text-gray-600 mt-1">Credentials are in the encrypted session cookie and expire after 60 minutes.</p>
        <div class="flex flex-wrap gap-3 mt-4">
            <form method="POST" action="{{ route('shopify.products.run') }}">@csrf<button class="border rounded px-4 py-2">List 10 products</button></form>
            <form method="POST" action="{{ route('shopify.locations.run') }}">@csrf<button class="border rounded px-4 py-2">List 10 locations</button></form>
            <form method="POST" action="{{ route('shopify.credentials.forget') }}">@csrf @method('DELETE')<button class="border border-red-300 text-red-700 rounded px-4 py-2">Forget credentials</button></form>
        </div>
    </div>
    @endif

    @foreach (['products' => $products, 'locations' => $locations] as $label => $items)
        @if (is_array($items))
        <div><h2 class="text-xl font-semibold capitalize">{{ $label }} ({{ count($items) }})</h2><pre class="mt-3 max-h-96 overflow-auto rounded bg-gray-900 p-4 text-xs text-gray-100">{{ json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
        @endif
    @endforeach
</div>
@endsection
