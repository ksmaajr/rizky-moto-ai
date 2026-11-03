<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class ImageGenerationController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:1', 'max:4000'],
            'model' => ['sometimes', 'string', 'max:100'],
            'size' => ['sometimes', Rule::in(['1024x1024', '1536x1024', '1024x1536', 'auto'])],
            'quality' => ['sometimes', Rule::in(['low', 'medium', 'high', 'auto'])],
            'n' => ['sometimes', 'integer', 'min:1', 'max:4'],
        ]);

        $providerKey = trim((string) config('api_gateway.provider_key'));
        if ($providerKey === '') {
            return response()->json([
                'error' => [
                    'message' => 'Image provider is not configured on the server.',
                    'type' => 'configuration_error',
                ],
            ], 503);
        }

        $payload = [
            'model' => $validated['model'] ?? config('api_gateway.model'),
            'prompt' => $validated['prompt'],
            'size' => $validated['size'] ?? '1024x1024',
            'quality' => $validated['quality'] ?? 'medium',
            'n' => $validated['n'] ?? 1,
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($providerKey)
                ->connectTimeout(10)
                ->timeout((int) config('api_gateway.timeout'))
                ->post(rtrim((string) config('api_gateway.base_url'), '/') . '/images/generations', $payload);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type', 'application/json'));
        } catch (ConnectionException) {
            return response()->json([
                'error' => [
                    'message' => 'The image provider could not be reached. Please retry shortly.',
                    'type' => 'provider_connection_error',
                ],
            ], 502);
        }
    }
}
