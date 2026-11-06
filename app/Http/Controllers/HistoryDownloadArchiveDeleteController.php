<?php

namespace App\Http\Controllers;

use App\Models\HistoryDownloadArchive;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HistoryDownloadArchiveDeleteController
{
    public function __invoke(Request $request, HistoryDownloadArchive $archive, ActivityLogService $activityLog)
    {
        abort_unless((int) $archive->user_id === (int) $request->user()->id, 404);
        abort_if(in_array($archive->status, ['queued', 'processing'], true), 409, 'Arsip yang sedang diproses tidak dapat dihapus.');

        if ($archive->file_path && Storage::disk('local')->exists($archive->file_path)) {
            Storage::disk('local')->delete($archive->file_path);
        }

        $activityLog->log(
            'history_bulk_download_delete',
            'download',
            'success',
            'Arsip bulk download dihapus',
            $archive->file_name,
            ['user_id' => $request->user()->id, 'archive_id' => $archive->id]
        );

        $archive->delete();

        return back()->with('status', 'Arsip bulk download berhasil dihapus.');
    }
}
