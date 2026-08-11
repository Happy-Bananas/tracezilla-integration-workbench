@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6 py-16">
    <h1 class="text-4xl font-bold">Check integration credentials before writing code</h1>
    <p class="mt-4 text-lg text-gray-600">This local workbench performs small, read-only API requests. Credentials stay in your encrypted browser session and are never written to the repository.</p>
    <div class="grid md:grid-cols-2 gap-6 mt-10">
        <a href="{{ route('shopify.test') }}" class="block bg-white border rounded p-6 hover:border-blue-500"><h2 class="text-2xl font-semibold">Shopify</h2><p class="mt-2 text-gray-600">Validate client credentials and inspect products or locations.</p></a>
        <a href="{{ route('tracezilla.test') }}" class="block bg-white border rounded p-6 hover:border-blue-500"><h2 class="text-2xl font-semibold">Tracezilla</h2><p class="mt-2 text-gray-600">Validate an API key and inspect a small SKU sample.</p></a>
    </div>
</div>
@endsection
