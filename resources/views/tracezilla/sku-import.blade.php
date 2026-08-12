@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-2">Import Shopify SKUs into Tracezilla</h1>
    <p class="text-gray-600 mb-6">Preview or create Tracezilla SKUs for Shopify variants whose SKU codes do not already exist.</p>

    <div class="bg-white border p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Configuration</h2>
        <table>
            <tr><th class="text-left pr-6 py-1">Shopify API</th><td>{{ $configuration['shopify'] ? '✅ Configured' : '❌ Missing' }}</td></tr>
            <tr><th class="text-left pr-6 py-1">Tracezilla API</th><td>{{ $configuration['tracezilla'] ? '✅ Configured' : '❌ Missing' }}</td></tr>
        </table>
    </div>

    @unless ($configuration['ready'])
        <div role="alert" class="mb-6 border border-red-200 bg-red-50 p-4 text-red-800">
            <strong>Configuration required:</strong> Add the missing Shopify and Tracezilla credentials to <code>.env</code>, then run
            <code>docker compose exec app php artisan config:clear</code>.
        </div>
    @endunless

    @if ($errors->any())
        <div role="alert" class="mb-6 border border-red-200 bg-red-50 p-4 text-red-800">{{ $errors->first() }}</div>
    @endif
    @if ($error)
        <div role="alert" class="mb-6 border border-red-200 bg-red-50 p-4 text-red-800"><strong>Error:</strong> {{ $error }}</div>
    @endif

    <form id="sku-import-form" method="POST" action="{{ route('tracezilla.sku-import.run') }}" class="bg-white border p-6">
        @csrf
        <label class="flex items-center gap-3 mb-4">
            <input id="dry-run" type="checkbox" name="dry_run" value="1" {{ old('dry_run', '1') ? 'checked' : '' }} {{ $configuration['ready'] ? '' : 'disabled' }}>
            <span><strong>Dry run</strong> — show what would happen without updating Tracezilla</span>
        </label>
        <label class="block mb-5">Maximum Shopify variants (optional)
            <input type="number" name="limit" min="1" value="{{ old('limit') }}" class="mt-1 block w-40 border rounded p-2" {{ $configuration['ready'] ? '' : 'disabled' }}>
        </label>
        <input id="confirm-write" type="hidden" name="confirm_write" value="">
        <button type="submit" {{ $configuration['ready'] ? '' : 'disabled' }}
            class="text-white bg-brand box-border border border-transparent hover:bg-brand-strong disabled:bg-gray-400 disabled:cursor-not-allowed font-medium rounded-base text-sm px-4 py-2.5">
            Run SKU import
        </button>
    </form>

    @if ($result)
        <div class="mt-6 border border-green-200 bg-green-50 p-4 text-green-800">
            <strong>{{ $result['summary']['dry_run'] ? 'Dry run completed.' : 'Import completed.' }}</strong>
            Created: {{ $result['summary']['created_count'] }}; would create: {{ $result['summary']['would_create_count'] }}; skipped: {{ $result['summary']['skipped_count'] }}; failed: {{ $result['summary']['failed_count'] }}.
        </div>
        <pre class="text-xs mt-4 bg-gray-900 text-gray-100 p-4 rounded overflow-auto" style="max-height: 24rem">{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    @endif
</div>

<dialog id="write-confirmation" class="rounded border p-0 shadow-xl backdrop:bg-black/40">
    <div class="max-w-lg p-6">
        <h2 class="text-xl font-semibold">Update the Tracezilla database?</h2>
        <p class="mt-3 text-gray-700">Dry run is disabled. Continuing can create missing SKUs in the configured Tracezilla team.</p>
        <div class="mt-6 flex justify-end gap-3">
            <button id="cancel-write" type="button" class="border rounded px-4 py-2">Cancel</button>
            <button id="approve-write" type="button" class="bg-red-700 text-white rounded px-4 py-2">Yes, update Tracezilla</button>
        </div>
    </div>
</dialog>

<script>
    const form = document.getElementById('sku-import-form');
    const dryRun = document.getElementById('dry-run');
    const confirmation = document.getElementById('write-confirmation');
    const confirmed = document.getElementById('confirm-write');

    function requestWriteConfirmation() {
        confirmed.value = '';
        confirmation.showModal();
    }

    dryRun?.addEventListener('change', () => {
        if (!dryRun.checked) requestWriteConfirmation();
        else confirmed.value = '';
    });
    form?.addEventListener('submit', event => {
        if (!dryRun.checked && confirmed.value !== 'yes') {
            event.preventDefault();
            requestWriteConfirmation();
        }
    });
    document.getElementById('cancel-write')?.addEventListener('click', () => {
        dryRun.checked = true;
        confirmed.value = '';
        confirmation.close();
    });
    document.getElementById('approve-write')?.addEventListener('click', () => {
        confirmed.value = 'yes';
        confirmation.close();
    });
</script>
@endsection
