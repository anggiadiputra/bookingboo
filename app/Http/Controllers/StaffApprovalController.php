<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffApprovalController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    /**
     * Daftar staf admin/platform beserta status persetujuannya.
     */
    public function index(Request $request): View
    {
        $staff = User::whereIn('role', [User::ROLE_SUPPORT, User::ROLE_FINANCE, User::ROLE_ADMIN])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.staff.index', compact('staff'));
    }

    /**
     * Form untuk membuat akun staf baru.
     */
    public function create(): View
    {
        return view('admin.staff.create');
    }

    /**
     * Simpan akun staf baru (status pending, menunggu persetujuan).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:support,finance,admin'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $staff = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'status' => User::STATUS_PENDING,
            'password' => Hash::make($validated['password']),
        ]);

        $this->audit->log($request->user(), 'staff.created',
            "Membuat akun staf {$staff->name} ({$staff->public_id}).",
            $staff,
            ['staff_role' => $staff->role]);

        return redirect()->route('admin.staff.index')
            ->with('status', 'Akun staf berhasil dibuat dan menunggu persetujuan.');
    }

    /**
     * Setujui akun staf sehingga dapat mengakses panel.
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $user->update([
            'status' => User::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);

        $this->audit->log($request->user(), 'staff.approved',
            "Menyetujui akun staf {$user->name} ({$user->public_id}).",
            $user,
            ['staff_role' => $user->role]);

        return back()->with('status', "Akun {$user->name} telah disetujui.");
    }

    /**
     * Tolak akun staf.
     */
    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $user->update([
            'status' => User::STATUS_REJECTED,
            'approved_at' => null,
        ]);

        $this->audit->log($request->user(), 'staff.rejected',
            "Menolak akun staf {$user->name} ({$user->public_id}).",
            $user,
            ['staff_role' => $user->role]);

        return back()->with('status', "Akun {$user->name} telah ditolak.");
    }
}
