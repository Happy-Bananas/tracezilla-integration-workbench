<?php

namespace App\Http\Controllers;

use App\Clients\TracezillaClient;
use App\Services\TracezillaSkuService;
use Throwable;

class TracezillaTestController extends Controller
{
    protected TracezillaClient $client;

    public function __construct()
    {
        $this->client = new TracezillaClient();
    }

    /**
     * Display the Tracezilla test page.
     */
    public function show()
    {
        return view('tracezilla.test', [
            'config' => config('services.tracezilla'),
            'result' => null,
            'error'  => null,
        ]);
    }

    /**
     * Test the Tracezilla connection.
     */
    public function test()
    {
        try {
            $this->client
                ->http()
                ->get('/skus', [
                    'perPage' => 1,
                ])
                ->throw();

            return view('tracezilla.test', [
                'config' => config('services.tracezilla'),
                'result' => [
                    'message' => 'Successfully connected to the Tracezilla API.',
                ],
                'error' => null,
            ]);
        } catch (Throwable $e) {
            return view('tracezilla.test', [
                'config' => config('services.tracezilla'),
                'result' => null,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * List SKUs.
     */
    public function listSkus(TracezillaSkuService $skus)
    {
        try {
            $result = $skus->listSkus(10);

            return view('tracezilla.test', [
                'config' => config('services.tracezilla'),
                'result' => [
                    'message' => count($result) === 0
                        ? 'No SKUs found in Tracezilla.'
                        : sprintf('%d Tracezilla SKU(s) returned.', count($result)),
                    'response' => $result,
                ],
                'error' => null,
            ]);
        } catch (Throwable $e) {
            return view('tracezilla.test', [
                'config' => config('services.tracezilla'),
                'result' => null,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * List warehouse locations.
     */
    public function listLocations()
    {
        //
    }
}
