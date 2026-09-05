<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\CaregiverDocument;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaregiverVerificationController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    /**
     * Daftar caregiver beserta status verifikasi dan dokumennya.
     */
    public function index(): View
    {
        $caregivers = Caregiver::with(['user', 'documents'])
            ->orderByRaw("CASE verification_status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.caregivers.index', compact('caregivers'));
    }

    /**
     * Detail dokumen verifikasi seorang caregiver.
     */
    public function show(Caregiver $caregiver): View
    {
        $caregiver->load(['user', 'documents']);

        return view('admin.caregivers.show', compact('caregiver'));
    }

    /**
     * Setujui sebuah dokumen caregiver.
     */
    public function approveDocument(Request $request, CaregiverDocument $document): RedirectResponse
    {
        $document->update([
            'status' => CaregiverDocument::STATUS_APPROVED,
            'rejection_reason' => null,
            'reviewed_at' => now(),
        ]);

        $document->caregiver->syncVerificationStatus();

        $this->audit->log($request->user(), 'caregiver.document_approved',
            "Menyetujui dokumen {$document->type} caregiver {$document->caregiver->user->name}.",
            $document,
            ['caregiver_id' => $document->caregiver_id, 'type' => $document->type]);

        return back()->with('status', 'Dokumen disetujui.');
    }

    /**
     * Tolak sebuah dokumen caregiver.
     */
    public function rejectDocument(Request $request, CaregiverDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $document->update([
            'status' => CaregiverDocument::STATUS_REJECTED,
            'rejection_reason' => $validated['rejection_reason'],
            'reviewed_at' => now(),
        ]);

        $document->caregiver->syncVerificationStatus();

        $this->audit->log($request->user(), 'caregiver.document_rejected',
            "Menolak dokumen {$document->type} caregiver {$document->caregiver->user->name}.",
            $document,
            ['caregiver_id' => $document->caregiver_id, 'type' => $document->type, 'reason' => $validated['rejection_reason']]);

        return back()->with('status', 'Dokumen ditolak.');
    }

    /**
     * Setujui caregiver secara keseluruhan (verification_status = verified).
     */
    public function approve(Request $request, Caregiver $caregiver): RedirectResponse
    {
        $caregiver->update([
            'verification_status' => Caregiver::VERIFICATION_VERIFIED,
        ]);

        $this->audit->log($request->user(), 'caregiver.verified',
            "Memverifikasi caregiver {$caregiver->user->name}.",
            $caregiver,
            ['caregiver_user_id' => $caregiver->user_id]);

        return back()->with('status', "Caregiver {$caregiver->user->name} telah diverifikasi.");
    }

    /**
     * Tolak caregiver secara keseluruhan.
     */
    public function reject(Request $request, Caregiver $caregiver): RedirectResponse
    {
        $caregiver->update([
            'verification_status' => Caregiver::VERIFICATION_REJECTED,
        ]);

        $this->audit->log($request->user(), 'caregiver.rejected',
            "Menolak verifikasi caregiver {$caregiver->user->name}.",
            $caregiver,
            ['caregiver_user_id' => $caregiver->user_id]);

        return back()->with('status', "Caregiver {$caregiver->user->name} ditolak.");
    }
}
