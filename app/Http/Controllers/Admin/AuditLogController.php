<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel audit log (support & admin): riwayat aktivitas penting platform.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = (string) $request->query('action', '');
        $actorId = (string) $request->query('actor', '');
        $search = trim((string) $request->query('q', ''));

        $query = AuditLog::query()->with('user')->latest();

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($actorId !== '') {
            $query->where('user_id', (int) $actorId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('public_id', 'like', "%{$search}%"));
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        $actions = AuditLog::ACTION_LABELS;
        asort($actions);

        // Aktor unik dari log yang sudah ada (untuk dropdown filter).
        $actors = User::whereIn('id', AuditLog::query()->select('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get();

        return view('admin.audit-logs.index', compact('logs', 'actions', 'actorId', 'action', 'search', 'actors'));
    }
}
