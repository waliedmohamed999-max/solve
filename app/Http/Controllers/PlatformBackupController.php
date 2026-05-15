<?php

namespace App\Http\Controllers;

use App\Support\PlatformAudit;
use App\Support\PlatformBackup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformBackupController extends Controller
{
    public function latest(): JsonResponse
    {
        return response()->json([
            'backup' => PlatformBackup::latest(),
            'directory' => PlatformBackup::directory(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $backup = PlatformBackup::create((string) $request->input('label', 'admin'));

        PlatformAudit::activity('backup_created', 'platform_backup', $backup['path'], [
            'path' => $backup['path'],
            'checksum' => $backup['checksum'],
        ], $request);

        return response()->json(['backup' => $backup], 201);
    }
}
