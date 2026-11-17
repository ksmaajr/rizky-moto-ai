<?php

namespace App\Http\Controllers;

use App\Models\HistoryDownloadArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HistoryDownloadArchiveController
{
    public function __invoke(Request $request, HistoryDownloadArchive $archive)
    {
        abort_unless((int) $archive->user_id === (int) $request->user()->id, 404);
        abort_unless($archive->status === 'completed' && $archive->file_path, 404);
        abort_unless(Storage::disk('local')->exists($archive->file_path), 404);

        return Storage::disk('local')->download(
            $archive->file_path,
            $archive->file_name ?: 'recent-history.zip',
            ['Content-Type' => 'application/zip']
        );
    }
}
