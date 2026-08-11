<?php

namespace App\Http\Controllers;

use App\Clients\TracezillaClient;
use Illuminate\Http\Request;
use Throwable;

class TracezillaTestController extends Controller
{
    public function show(Request $request)
    {
        return $this->page($request);
    }

    public function test(Request $request)
    {
        $credentials = $this->credentials($request);

        try {
            (new TracezillaClient($credentials))->http()->get('/skus', ['perPage' => 1])->throw();
            $request->session()->put('workbench.tracezilla', $credentials);

            return $this->page($request, ['message' => 'Tracezilla credentials are valid.']);
        } catch (Throwable) {
            return $this->page($request, error: 'Tracezilla could not authenticate or complete the API check. Verify the URL, team slug, and API key.');
        }
    }

    public function listSkus(Request $request)
    {
        $credentials = $request->session()->get('workbench.tracezilla');

        if (! is_array($credentials)) {
            return $this->page($request, error: 'Validate Tracezilla credentials before running a read-only check.');
        }

        try {
            $payload = (new TracezillaClient($credentials))->http()->get('/skus', [
                'sortBy' => 'sku_code', 'sortDirection' => 'asc', 'perPage' => 10,
            ])->throw()->json();
            $skus = is_array($payload) ? data_get($payload, 'data', []) : [];

            return $this->page($request, [
                'message' => count($skus).' Tracezilla SKU(s) returned.',
                'response' => $skus,
            ]);
        } catch (Throwable) {
            return $this->page($request, error: 'Tracezilla could not complete the read-only SKU request.');
        }
    }

    public function forget(Request $request)
    {
        $request->session()->forget('workbench.tracezilla');

        return redirect()->route('tracezilla.test')->with('status', 'Tracezilla credentials removed from this browser session.');
    }

    private function credentials(Request $request): array
    {
        $validated = $request->validate([
            'base_url' => ['required', 'url:http,https', 'max:255'],
            'team_slug' => ['required', 'string', 'max:255'],
            'api_key' => ['required', 'string', 'max:1000'],
        ]);

        return $validated + ['timeout' => 30, 'connect_timeout' => 10];
    }

    private function page(Request $request, ?array $result = null, ?string $error = null)
    {
        $saved = $request->session()->get('workbench.tracezilla');

        return response()->view('tracezilla.test', [
            'saved' => is_array($saved) ? $saved : null,
            'result' => $result,
            'error' => $error,
        ])->header('Cache-Control', 'no-store, private');
    }
}
