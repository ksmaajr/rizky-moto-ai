<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ActivityLogFeedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $limit = min(max((int) $request->input('limit', 100), 1), 100);

            $logs = ActivityLog::query()
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(static function (ActivityLog $log): array {
                    $metadata = $log->metadata;

                    if (!is_array($metadata)) {
                        $metadata = [];
                    }

                    return [
                        'id' => (int) $log->id,
                        'category' => (string) ($log->category ?? 'system'),
                        'action' => (string) ($log->action ?? ''),
                        'status' => (string) ($log->status ?? 'info'),
                        'title' => (string) ($log->title ?? $log->action ?? 'Activity'),
                        'description' => (string) ($log->description ?? ''),
                        'metadata' => $metadata,
                        'created_at' => optional($log->created_at)->toISOString(),
                    ];
                })
                ->values()
                ->all();

            return response()
                ->json([
                    'ok' => true,
                    'total' => count($logs),
                    'logs' => $logs,
                    'server_time' => now()->toISOString(),
                ])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'total' => 0,
                'logs' => [],
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Activity feed gagal dimuat.',
            ], 500);
        }
    }

    public function destroy(): JsonResponse
    {
        try {
            $deleted = ActivityLog::query()->delete();

            return response()->json([
                'ok' => true,
                'deleted' => $deleted,
                'message' => 'Activity Logs berhasil dibersihkan.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Gagal membersihkan Activity Logs.',
            ], 500);
        }
    }
}
