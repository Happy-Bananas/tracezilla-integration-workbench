@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-2">Shopify Locations</h1>
    <p class="text-gray-600 mb-6">List every location available to the configured Shopify app. This operation is read-only.</p>

    <div class="bg-white border p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Configuration</h2>
        <table>
            <tr><th class="text-left pr-6 py-1">Shopify API</th><td>{{ $configuration['shopify'] ? '✅ Configured' : '❌ Missing' }}</td></tr>
            <tr><th class="text-left pr-6 py-1">Configured scope</th><td>{{ $configuration['scope'] ?: 'Missing' }}</td></tr>
        </table>
    </div>

    @unless ($configuration['shopify'])
        <div role="alert" class="mb-6 border border-red-200 bg-red-50 p-4 text-red-800">
            <strong>Configuration required:</strong> Add the Shopify credentials to <code>.env</code>, then run
            <code>docker compose exec app php artisan config:clear</code>.
        </div>
    @endunless

    @if ($error)
        <div role="alert" class="mb-6 border border-red-200 bg-red-50 p-4 text-red-800"><strong>Error:</strong> {{ $error }}</div>
    @endif

    <form method="POST" action="{{ route('shopify.locations.run') }}" class="bg-white border p-6">
        @csrf
        <button type="submit" {{ $configuration['shopify'] ? '' : 'disabled' }}
            class="text-white bg-brand box-border border border-transparent hover:bg-brand-strong disabled:bg-gray-400 disabled:cursor-not-allowed font-medium rounded-base text-sm px-4 py-2.5">
            List Shopify Locations
        </button>
    </form>

    @if ($result)
        <div class="mt-6 border border-green-200 bg-green-50 p-4 text-green-800">
            <strong>Locations retrieved.</strong>
            {{ $result['count'] }} Shopify location(s) returned.
        </div>
        @if ($result['count'] === 0)
            <div class="mt-4 border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">
                No Shopify locations are available to this app.
            </div>
        @endif
        <pre class="text-xs mt-4 bg-gray-900 text-gray-100 p-4 rounded overflow-auto" style="max-height: 24rem">{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    @endif
</div>
@endsection
