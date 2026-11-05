<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->query('user_id');
        $action = $request->query('action');

        $users = User::orderBy('name')->get();

        $logs = AuditLog::with('user')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $actions = AuditLog::select('action')->distinct()->pluck('action');

        return view('InstitutionAdmin.InstitutionAdminAuditLogsIndex', compact('logs', 'users', 'userId', 'action', 'actions'));
    }
}
