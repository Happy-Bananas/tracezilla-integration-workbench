@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">tracezilla Connection Test</h1>

    <div class="bg-white border p-6 mb-6 mt-4">
        <h2 class="text-xl font-semibold mb-4">Configuration</h2>

        <table class="w-auto">
            <tbody>
                <tr>
                    <th class="text-left font-medium pr-6 py-1">Base URL</th>
                    <td>{{ $config['base_url'] ?: 'Missing' }}</td>
                </tr>

                <tr>
                    <th class="text-left font-medium pr-6 py-1">Team Slug</th>
                    <td>{{ $config['team_slug'] ?: 'Missing' }}</td>
                </tr>

                <tr>
                    <th class="text-left font-medium pr-6 py-1">API Key</th>
                    <td>{{ $config['api_key'] ? '✅ Configured' : '❌ Missing' }}</td>
                </tr>

            </tbody>
        </table>
    </div>

    <div class="my-8 flex gap-4">

        <form method="POST" action="{{ route('tracezilla.test.run') }}">
            @csrf
            <button
                type="submit"
                class="text-white bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">
                Test Tracezilla Connection
            </button>
        </form>

        <form method="POST" action="{{ route('tracezilla.skus.run') }}">
            @csrf
            <button
                type="submit"
                class="text-white bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">
                List 10 SKUs
            </button>
        </form>

    </div>

    <div class="my-8 border-t pt-6">
        <a href="{{ route('tracezilla.sku-import') }}"
           class="inline-block text-white bg-brand hover:bg-brand-strong font-medium rounded-base text-sm px-4 py-2.5">
            Import Shopify SKUs into Tracezilla
        </a>
    </div>

    @if ($result)
        <div class="mt-4 bg-green-50 border border-green-200 text-green-800 p-4">
            <strong>Success:</strong> {{ $result['message'] }}
        </div>

        <pre
            style="max-height: 24rem; overflow: auto;"
            class="text-xs mt-4 bg-gray-900 text-gray-100 p-4 rounded"
>{{ json_encode($result, JSON_PRETTY_PRINT) }}</pre>
    @endif

    @if ($error)
        <div class="mt-4 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
            <strong>Error:</strong> {{ $error }}
        </div>
    @endif

</div>



@endsection
