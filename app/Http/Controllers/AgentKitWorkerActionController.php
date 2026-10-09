<?php

namespace App\Http\Controllers;

use App\Services\AgentKitWorkerManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AgentKitWorkerActionController
{
    public function __invoke(Request $request, string $action): JsonResponse
    {
        abort_unless(in_array($action, ['start', 'stop', 'restart'], true), 404);

        try {
            $result = match ($action) {
                'start' => app(AgentKitWorkerManager::class)->start(),
                'stop' => app(AgentKitWorkerManager::class)->stop(),
                'restart' => app(AgentKitWorkerManager::class)->restart(),
            };

            return response()->json($result);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
