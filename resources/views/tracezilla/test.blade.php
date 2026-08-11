@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6 space-y-6">
    <div><h1 class="text-3xl font-bold">Tracezilla credential check</h1><p class="mt-2 text-gray-600">Validate API access and optionally retrieve a small read-only SKU sample.</p></div>
    @if (session('status')) <div class="border border-green-200 bg-green-50 p-4 text-green-800">{{ session('status') }}</div> @endif
    @if ($errors->any()) <div class="border border-red-200 bg-red-50 p-4 text-red-800">Check the credential fields and try again.</div> @endif
    @if ($error) <div class="border border-red-200 bg-red-50 p-4 text-red-800"><strong>Not validated:</strong> {{ $error }}</div> @endif
    @if ($result) <div class="border border-green-200 bg-green-50 p-4 text-green-800"><strong>Success:</strong> {{ $result['message'] }}</div> @endif

    <form method="POST" action="{{ route('tracezilla.test.run') }}" class="bg-white border p-6 grid md:grid-cols-2 gap-4">
        @csrf
        <label class="block md:col-span-2">Base URL<input type="url" name="base_url" value="{{ old('base_url', $saved['base_url'] ?? 'https://app.tracezilla.com') }}" required class="mt-1 w-full border rounded p-2"></label>
        <label class="block">Team slug<input name="team_slug" value="{{ old('team_slug', $saved['team_slug'] ?? '') }}" required class="mt-1 w-full border rounded p-2"></label>
        <label class="block">API key<input type="password" name="api_key" value="" placeholder="{{ $saved ? 'Enter again to revalidate' : '' }}" required autocomplete="new-password" class="mt-1 w-full border rounded p-2"></label>
        <div class="md:col-span-2"><button class="bg-blue-700 text-white rounded px-4 py-2">Validate and keep for this session</button></div>
    </form>

    @if ($saved)
    <div class="border bg-white p-6">
        <h2 class="text-xl font-semibold">Read-only checks</h2>
        <p class="text-sm text-gray-600 mt-1">Credentials are in the encrypted session cookie and expire after 60 minutes.</p>
        <div class="flex gap-3 mt-4">
            <form method="POST" action="{{ route('tracezilla.skus.run') }}">@csrf<button class="border rounded px-4 py-2">List 10 SKUs</button></form>
            <form method="POST" action="{{ route('tracezilla.credentials.forget') }}">@csrf @method('DELETE')<button class="border border-red-300 text-red-700 rounded px-4 py-2">Forget credentials</button></form>
        </div>
    </div>
    @endif
    @if (isset($result['response'])) <pre class="max-h-96 overflow-auto rounded bg-gray-900 p-4 text-xs text-gray-100">{{ json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre> @endif
</div>
@endsection
