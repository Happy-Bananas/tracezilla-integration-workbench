@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Shopify Connection Test</h1>

    <div class="bg-white border p-6 mb-6 mt-4">
        <h2 class="text-xl font-semibold mb-4">Configuration</h2>

        <table class="w-auto">
            <tbody>
                <tr>
                    <th class="text-left font-medium pr-6 py-1">Shop URL</th>
                    <td>{{ $config['shop_url'] ?: 'Missing' }}</td>
                </tr>

                <tr>
                    <th class="text-left font-medium pr-6 py-1">Scope</th>
                    <td>{{ $config['scope'] ?: 'Missing' }}</td>
                </tr>

                <tr>
                    <th class="text-left font-medium pr-6 py-1">Requested API Version</th>
                    <td>{{ $config['api_version'] ?: 'Missing' }}</td>
                </tr>

                <tr>
                    <th class="text-left font-medium pr-6 py-1">Client ID</th>
                    <td>{{ $config['client_id'] ? '✅ Configured' : '❌ Missing' }}</td>
                </tr>

                <tr>
                    <th class="text-left font-medium pr-6 py-1">Client Secret</th>
                    <td>{{ $config['client_secret'] ? '✅ Configured' : '❌ Missing' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

<div class="my-8 flex gap-4">

    <form method="POST" action="{{ route('shopify.test.run') }}">
        @csrf
        <button
            type="submit"
            class="text-white bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">
            Test Shopify Connection
        </button>
    </form>

    <form method="POST" action="{{ route('shopify.products.run') }}">
        @csrf
        <button
            type="submit"
            class="text-white bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">
            List 10 Products
        </button>
    </form>

</div>

    <div class="my-8 border-t pt-6">
        <a href="{{ route('shopify.locations') }}"
           class="inline-block text-white bg-brand hover:bg-brand-strong font-medium rounded-base text-sm px-4 py-2.5">
            List Shopify Locations
        </a>
    </div>

    @if($products)
        <div class="mt-8">
            <h2 class="text-lg font-semibold mb-4">
                Products {{ count($products) }} returned
            </h2>

            <pre
    style="max-height: 24rem; overflow: auto;"
    class="text-xs mt-4 bg-gray-900 text-gray-100 p-4 rounded"
>
{{ json_encode($products, JSON_PRETTY_PRINT) }}
</pre>

            <!-- <pre class="text-xs mt-4 bg-gray-900 text-gray-100 p-4 rounded overflow-auto">{{ json_encode($products, JSON_PRETTY_PRINT) }}</pre> -->

        </div>
    @endif

@if ($result)
    <div class="mt-4 bg-green-50 border border-green-200 text-green-800 p-4">
        <strong>Success:</strong> {{ $result['message'] }}
    </div>

    <pre class="text-xs mt-4 bg-gray-900 text-gray-100 p-4 rounded overflow-auto max-h-[24rem]">
{{ json_encode($result, JSON_PRETTY_PRINT) }}
    </pre>
@endif

    @if ($error)
        <div class="mt-4 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
            <strong>Error:</strong> {{ $error }}
        </div>
    @endif
</div>
@endsection
