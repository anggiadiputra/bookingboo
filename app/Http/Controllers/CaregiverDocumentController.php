<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\CaregiverDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CaregiverDocumentController extends Controller
{
    /**
     * Tampilkan halaman dokumen verifikasi caregiver.
     */
    public function index(Request $request): View
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver) {
            abort(404, 'Profil caregiver tidak ditemukan.');
        }

        $documents = $caregiver->documents()->orderBy('created_at', 'desc')->get();

        return view('caregiver.documents', compact('caregiver', 'documents'));
    }

    /**
     * Simpan dokumen verifikasi baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver) {
            abort(404, 'Profil caregiver tidak ditemukan.');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(CaregiverDocument::TYPES))],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        // Dokumen lama pada jenis yang sama yang belum disetujui digantikan unggahan baru.
        $replaced = $caregiver->documents()
            ->where('type', $validated['type'])
            ->whereIn('status', [CaregiverDocument::STATUS_PENDING, CaregiverDocument::STATUS_REJECTED])
            ->get();

        $path = $request->file('document')->store('caregiver-documents', 'public');

        $caregiver->documents()->create([
            'type' => $validated['type'],
            'file_path' => $path,
            'status' => CaregiverDocument::STATUS_PENDING,
        ]);

        foreach ($replaced as $old) {
            Storage::disk('public')->delete($old->file_path);
            $old->delete();
        }

        $caregiver->syncVerificationStatus();

        return back()->with('status', 'Dokumen berhasil diunggah dan menunggu verifikasi admin.');
    }

    /**
     * Hapus dokumen milik caregiver yang login.
     */
    public function destroy(Request $request, CaregiverDocument $document): RedirectResponse
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver || $document->caregiver_id !== $caregiver->id) {
            abort(403);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        $caregiver->syncVerificationStatus();

        return back()->with('status', 'Dokumen berhasil dihapus.');
    }
}
