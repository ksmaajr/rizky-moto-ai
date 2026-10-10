<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $usedByOwner = [];

        DB::table('agent_ai_credentials')
            ->orderBy('user_id')
            ->orderBy('id')
            ->select(['id', 'user_id', 'name'])
            ->chunk(200, function ($credentials) use (&$usedByOwner): void {
                foreach ($credentials as $credential) {
                    $ownerKey = $credential->user_id === null
                        ? 'global'
                        : 'user:' . $credential->user_id;

                    $usedByOwner[$ownerKey] ??= [];
                    $base = trim((string) ($credential->name ?? ''));
                    if ($base === '') {
                        $base = 'Codex Account';
                    }
                    $candidate = mb_substr($base, 0, 120);
                    $suffix = 2;

                    while (isset($usedByOwner[$ownerKey][mb_strtolower($candidate)])) {
                        $tail = ' ' . $suffix++;
                        $candidate = mb_substr($base, 0, 120 - mb_strlen($tail)) . $tail;
                    }

                    $usedByOwner[$ownerKey][mb_strtolower($candidate)] = true;

                    if ((string) $credential->name !== $candidate) {
                        DB::table('agent_ai_credentials')
                            ->where('id', $credential->id)
                            ->update([
                                'name' => $candidate,
                                'updated_at' => now(),
                            ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Account labels are user-visible data; automatic rollback would risk
        // restoring duplicate names and overwriting later manual changes.
    }
};
