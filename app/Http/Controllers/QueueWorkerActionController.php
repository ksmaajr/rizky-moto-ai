<?php

namespace App\\Http\\Controllers;

use App\\Services\\QueueWorkerManager;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Throwable;

class QueueWorkerActionController
{
    public function __invoke(Request $request, string $action): JsonResponse
    {
        abort_unless(in_array($action, ['start', 'stop', 'restart'], true), 404);

        try {
            $result = match ($action) {
                'start' => app(QueueWorkerManager::class)->start(),
                'stop' => app(QueueWorkerManager::class)->stop(),
                'restart' => app(QueueWorkerManager::class)->restart(),
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
