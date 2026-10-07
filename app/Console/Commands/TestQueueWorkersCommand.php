<?php

namespace App\Console\Commands;

use App\Jobs\TestQueueWorkerJob;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestQueueWorkersCommand extends Command
{
    protected $signature = 'queue:test-workers
                            {count=3 : Jumlah test job yang di-dispatch}
                            {--sleep=5 : Durasi job menahan worker dalam detik}';

    protected $description = 'Dispatch test jobs untuk memverifikasi concurrency queue worker tanpa memanggil AI provider.';

    public function handle(): int
    {
        $count = max(1, min(10, (int) $this->argument('count')));
        $sleep = max(1, min(30, (int) $this->option('sleep')));
        $testId = (string) Str::uuid();

        $this->info("Queue worker concurrency test: {$testId}");
        $this->line("Dispatching {$count} job(s), masing-masing sleep {$sleep} detik...");

        for ($sequence = 1; $sequence <= $count; $sequence++) {
            TestQueueWorkerJob::dispatch(
                testId: $testId,
                sequence: $sequence,
                sleepSeconds: $sleep,
            );

            $this->line("  ✓ Job #{$sequence} queued");
        }

        $this->newLine();
        $this->info('Semua test job sudah masuk database queue.');
        $this->line('Pantau Dashboard → Global Activity untuk melihat PID worker yang mengeksekusi masing-masing job.');
        $this->line("Test ID: {$testId}");

        return self::SUCCESS;
    }
}
