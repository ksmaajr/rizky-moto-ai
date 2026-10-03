<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Create a generic activity log.
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
        /*
         * Keep technical metrics in metadata so the activity
         * system stays flexible for API, Generator, Store,
         * Template, Account, and future features.
         */
        if ($durationMs !== null) {
            $metadata['duration_ms'] = $durationMs;
        }

        if ($httpStatus !== null) {
            $metadata['http_status'] = $httpStatus;
        }

        return ActivityLog::query()->create([
            'user_id' => Auth::id(),

            'action' => $action,

            'category' => $category,

            'status' => $status,

            'title' => $title,

            'description' => $description,

            'metadata' => $metadata,
        ]);
    }

    /**
     * Success activity.
     */
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

    /**
     * Error activity.
     */
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

    /**
     * Warning activity.
     */
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

    /**
     * Processing activity.
     */
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

    /**
     * Informational activity.
     */
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