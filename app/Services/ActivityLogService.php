<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Generation;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Create a generic activity log.
     *
     * Queue workers do not have an authenticated HTTP session, so when a
     * generation log is written from a background job we resolve the owner
     * from the generation_id stored in metadata.
     */
    public function log(
        string $action,
        string $category,
        string $status,
        string $title,
        ?string $description = null,
        array $metadata = [],
        ?int $durationMs = null,
        ?int $httpStatus = null,
    ): ActivityLog {
        if ($durationMs !== null) {
            $metadata['duration_ms'] = $durationMs;
        }

        if ($httpStatus !== null) {
            $metadata['http_status'] = $httpStatus;
        }

        $userId = Auth::id();

        // Queue/worker context: Auth::id() is normally null.
        // Resolve the owner from the Generation so generator activity logs
        // remain attached to the correct user and visible in the dashboard.
        if ($userId === null && ! empty($metadata['generation_id'])) {
            $userId = Generation::query()
                ->whereKey((int) $metadata['generation_id'])
                ->value('user_id');
        }

        return ActivityLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'category' => $category,
            'status' => $status,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    public function success(
        string $action,
        string $category,
        string $title,
        ?string $description = null,
        array $metadata = [],
        ?int $durationMs = null,
        ?int $httpStatus = null,
    ): ActivityLog {
        return $this->log(
            action: $action,
            category: $category,
            status: 'success',
            title: $title,
            description: $description,
            metadata: $metadata,
            durationMs: $durationMs,
            httpStatus: $httpStatus,
        );
    }

    public function error(
        string $action,
        string $category,
        string $title,
        ?string $description = null,
        array $metadata = [],
        ?int $durationMs = null,
        ?int $httpStatus = null,
    ): ActivityLog {
        return $this->log(
            action: $action,
            category: $category,
            status: 'error',
            title: $title,
            description: $description,
            metadata: $metadata,
            durationMs: $durationMs,
            httpStatus: $httpStatus,
        );
    }

    public function warning(
        string $action,
        string $category,
        string $title,
        ?string $description = null,
        array $metadata = [],
        ?int $durationMs = null,
        ?int $httpStatus = null,
    ): ActivityLog {
        return $this->log(
            action: $action,
            category: $category,
            status: 'warning',
            title: $title,
            description: $description,
            metadata: $metadata,
            durationMs: $durationMs,
            httpStatus: $httpStatus,
        );
    }

    public function processing(
        string $action,
        string $category,
        string $title,
        ?string $description = null,
        array $metadata = [],
        ?int $durationMs = null,
        ?int $httpStatus = null,
    ): ActivityLog {
        return $this->log(
            action: $action,
            category: $category,
            status: 'processing',
            title: $title,
            description: $description,
            metadata: $metadata,
            durationMs: $durationMs,
            httpStatus: $httpStatus,
        );
    }

    public function info(
        string $action,
        string $category,
        string $title,
        ?string $description = null,
        array $metadata = [],
        ?int $durationMs = null,
        ?int $httpStatus = null,
    ): ActivityLog {
        return $this->log(
            action: $action,
            category: $category,
            status: 'info',
            title: $title,
            description: $description,
            metadata: $metadata,
            durationMs: $durationMs,
            httpStatus: $httpStatus,
        );
    }
}
