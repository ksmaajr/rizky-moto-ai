<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Env;

class TakeoverCommand extends Command
{
    protected $signature = 'takeover {action=status : on, off, or status}';

    protected $description = 'Enable, disable, or inspect the simulated takeover page';

    public function handle(): int
    {
        $action = $this->argument('action');

        if (! in_array($action, ['on', 'off', 'status'], true)) {
            $this->error('Gunakan: php artisan takeover on|off|status');

            return self::FAILURE;
        }

        if ($action === 'status') {
            $this->info('Simulasi: '.(config('takeover.enabled') ? 'AKTIF' : 'NONAKTIF'));

            return self::SUCCESS;
        }

        $path = $this->laravel->environmentFilePath();
        $handle = @fopen($path, 'r+');

        if ($handle === false) {
            $this->error('File .env belum ada atau tidak dapat ditulis. Jalankan sebagai user pemilik aplikasi.');

            return self::FAILURE;
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                $this->error('Tidak dapat mengunci .env. Coba kembali.');

                return self::FAILURE;
            }

            $contents = stream_get_contents($handle);
            $value = $action === 'on' ? 'true' : 'false';
            $line = 'TAKEOVER_ENABLED='.$value;
            $pattern = '/^[\t ]*(?:export[\t ]+)?TAKEOVER_ENABLED[\t ]*=[^\r\n]*/m';
            $updated = preg_replace($pattern, $line, $contents, -1, $count);

            if ($count === 0) {
                $newline = str_contains($contents, "\r\n") ? "\r\n" : "\n";
                $updated = rtrim($contents, "\r\n").$newline.$line.$newline;
            }

            rewind($handle);

            if (fwrite($handle, $updated) !== strlen($updated) || ! ftruncate($handle, strlen($updated)) || ! fflush($handle)) {
                $this->error('Gagal menyimpan .env. Periksa izin dan ruang disk sebelum melanjutkan.');

                return self::FAILURE;
            }

            Env::getRepository()->set('TAKEOVER_ENABLED', $value);

            if ($this->call('config:cache') !== self::SUCCESS) {
                $this->error('.env diperbarui, tetapi cache konfigurasi gagal dibuat. Perbaiki error lalu jalankan php artisan config:cache.');

                return self::FAILURE;
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $this->info('Simulasi: '.($action === 'on' ? 'AKTIF' : 'NONAKTIF'));

        return self::SUCCESS;
    }
}
