<?php

use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CaregiverVerificationController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\BookingChatController;
use App\Http\Controllers\CaregiverBookingController;
use App\Http\Controllers\CaregiverDocumentController;
use App\Http\Controllers\CaregiverNoteController;
use App\Http\Controllers\CaregiverProfileController;
use App\Http\Controllers\CaregiverPublicController;
use App\Http\Controllers\CaregiverScheduleController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\CustomerBookingController;
use App\Http\Controllers\CustomerFavoriteController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StaffApprovalController;
use App\Models\Announcement;
use App\Models\Caregiver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    $caregivers = Schema::hasTable('caregivers')
        ? Caregiver::where('verification_status', Caregiver::VERIFICATION_VERIFIED)
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->with('user')
            ->orderByDesc('rating')
            ->take(6)
            ->get()
        : collect();

    $announcements = Schema::hasTable('announcements')
        ? Announcement::live()->take(5)->get()
        : collect();

    return view('welcome', compact('caregivers', 'announcements'));
})->name('home');

// Daftar & pencarian caregiver publik (tanpa login)
Route::get('/caregivers', [CaregiverPublicController::class, 'index'])->name('caregivers.index');
// Profil publik caregiver (bisa diakses tanpa login)
Route::get('/caregivers/{caregiver}', [CaregiverPublicController::class, 'show'])->name('caregivers.show');

// Halaman publik legal: Syarat & Ketentuan dan Kebijakan Privasi
Route::view('/terms', 'terms')->name('terms');
Route::view('/privacy', 'privacy')->name('privacy');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'staff.approved'])
    ->name('dashboard');

// Dashboard khusus staf: admin (operasional), finance (keuangan), CS/support (dukungan)
Route::middleware(['auth', 'verified', 'staff.approved', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
});

Route::middleware(['auth', 'verified', 'staff.approved', 'role:finance'])->group(function () {
    Route::get('/finance/dashboard', [DashboardController::class, 'financeDashboard'])->name('finance.dashboard');
});

Route::middleware(['auth', 'verified', 'staff.approved', 'role:support'])->group(function () {
    Route::get('/cs/dashboard', [DashboardController::class, 'supportDashboard'])->name('support.dashboard');
});

// Webhook Midtrans (publik, diamankan dengan signature_key)
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Profil customer (khusus role customer)
Route::middleware(['auth', 'verified', 'role:customer'])->group(function () {
    Route::get('/customer/profile', [CustomerProfileController::class, 'edit'])->name('customer.profile.edit');
    Route::patch('/customer/profile', [CustomerProfileController::class, 'update'])->name('customer.profile.update');

    // Booking
    Route::get('/customer/bookings', [CustomerBookingController::class, 'index'])->name('customer.bookings.index');
    Route::get('/customer/bookings/{booking}', [CustomerBookingController::class, 'show'])->name('customer.bookings.show');
    Route::get('/customer/bookings/{booking}/invoice', [CustomerBookingController::class, 'invoice'])->name('customer.bookings.invoice');
    Route::patch('/customer/bookings/{booking}/cancel', [CustomerBookingController::class, 'cancel'])->name('customer.bookings.cancel');
    Route::get('/customer/bookings-baru/{caregiver}', [CustomerBookingController::class, 'create'])->name('customer.bookings.create');
    Route::post('/customer/bookings-baru/{caregiver}', [CustomerBookingController::class, 'store'])->name('customer.bookings.store');

    // Pembayaran
    Route::get('/customer/bookings/{booking}/bayar', [PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::post('/customer/bookings/{booking}/bayar/settle', [PaymentController::class, 'settle'])->name('payment.settle');
    Route::post('/customer/bookings/{booking}/bayar/expire', [PaymentController::class, 'expire'])->name('payment.expire');
    Route::get('/customer/bookings/{booking}/bayar/selesai', [PaymentController::class, 'finish'])->name('payment.finish');

    // Review publik customer ke caregiver setelah booking selesai
    Route::post('/customer/bookings/{booking}/review', [ReviewController::class, 'store'])->name('customer.bookings.review.store');

    // Komplain booking (customer)
    Route::post('/customer/bookings/{booking}/komplain', [ComplaintController::class, 'store'])->name('customer.bookings.complaint.store');

    // Caregiver favorit (FR-28)
    Route::post('/customer/caregivers/{caregiver}/favorite', [CustomerFavoriteController::class, 'toggle'])->name('customer.favorites.toggle');
});

// Profil caregiver (khusus role caregiver)
Route::middleware(['auth', 'verified', 'role:caregiver'])->group(function () {
    Route::get('/caregiver/profile', [CaregiverProfileController::class, 'edit'])->name('caregiver.profile.edit');
    Route::patch('/caregiver/profile', [CaregiverProfileController::class, 'update'])->name('caregiver.profile.update');

    Route::get('/caregiver/schedules', [CaregiverScheduleController::class, 'index'])->name('caregiver.schedules.index');
    Route::post('/caregiver/schedules', [CaregiverScheduleController::class, 'store'])->name('caregiver.schedules.store');
    Route::delete('/caregiver/schedules/{schedule}', [CaregiverScheduleController::class, 'destroy'])->name('caregiver.schedules.destroy');

    Route::get('/caregiver/documents', [CaregiverDocumentController::class, 'index'])->name('caregiver.documents.index');
    Route::post('/caregiver/documents', [CaregiverDocumentController::class, 'store'])->name('caregiver.documents.store');
    Route::delete('/caregiver/documents/{document}', [CaregiverDocumentController::class, 'destroy'])->name('caregiver.documents.destroy');

    // Booking masuk
    Route::get('/caregiver/bookings', [CaregiverBookingController::class, 'index'])->name('caregiver.bookings.index');
    Route::get('/caregiver/bookings/{booking}', [CaregiverBookingController::class, 'show'])->name('caregiver.bookings.show');
    Route::get('/caregiver/bookings/{booking}/invoice', [CaregiverBookingController::class, 'invoice'])->name('caregiver.bookings.invoice');
    Route::patch('/caregiver/bookings/{booking}/accept', [CaregiverBookingController::class, 'accept'])->name('caregiver.bookings.accept');
    Route::patch('/caregiver/bookings/{booking}/reject', [CaregiverBookingController::class, 'reject'])->name('caregiver.bookings.reject');
    Route::patch('/caregiver/bookings/{booking}/cancel', [CaregiverBookingController::class, 'cancel'])->name('caregiver.bookings.cancel');
    Route::post('/caregiver/bookings/{booking}/check-in', [CaregiverBookingController::class, 'checkIn'])->name('caregiver.bookings.check-in');
    Route::post('/caregiver/bookings/{booking}/check-out', [CaregiverBookingController::class, 'checkOut'])->name('caregiver.bookings.check-out');

    // Catatan privat caregiver terhadap customer setelah booking selesai
    Route::post('/caregiver/bookings/{booking}/note', [CaregiverNoteController::class, 'store'])->name('caregiver.bookings.note.store');

    // Komplain booking (caregiver) — endpoint sama, dibatasi peserta di controller
    Route::post('/caregiver/bookings/{booking}/komplain', [ComplaintController::class, 'store'])->name('caregiver.bookings.complaint.store');
});

// Chat customer-caregiver terkait booking (dipakai kedua peran; otorisasi di controller)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bookings/{booking}/chat', [BookingChatController::class, 'show'])->name('chat.show');
    Route::get('/bookings/{booking}/chat/messages', [BookingChatController::class, 'fetch'])->name('chat.fetch');
    Route::post('/bookings/{booking}/chat/kirim', [BookingChatController::class, 'send'])->name('chat.send');

    // Notifikasi in-app (FR-13)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // Pencairan dana (payout)
    Route::get('/caregiver/payouts', [PayoutController::class, 'index'])->name('caregiver.payouts.index');
    Route::post('/caregiver/payouts', [PayoutController::class, 'store'])->name('caregiver.payouts.store');
});

// Manajemen persetujuan staf admin/platform (khusus admin)
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/staff', [StaffApprovalController::class, 'index'])->name('admin.staff.index');
    Route::get('/admin/staff/create', [StaffApprovalController::class, 'create'])->name('admin.staff.create');
    Route::post('/admin/staff', [StaffApprovalController::class, 'store'])->name('admin.staff.store');
    Route::patch('/admin/staff/{user}/approve', [StaffApprovalController::class, 'approve'])->name('admin.staff.approve');
    Route::patch('/admin/staff/{user}/reject', [StaffApprovalController::class, 'reject'])->name('admin.staff.reject');

    Route::get('/admin/caregivers', [CaregiverVerificationController::class, 'index'])->name('admin.caregivers.index');
    Route::get('/admin/caregivers/{caregiver}', [CaregiverVerificationController::class, 'show'])->name('admin.caregivers.show');
    Route::patch('/admin/caregivers/{caregiver}/approve', [CaregiverVerificationController::class, 'approve'])->name('admin.caregivers.approve');
    Route::patch('/admin/caregivers/{caregiver}/reject', [CaregiverVerificationController::class, 'reject'])->name('admin.caregivers.reject');
    Route::patch('/admin/caregivers/documents/{document}/approve', [CaregiverVerificationController::class, 'approveDocument'])->name('admin.caregivers.documents.approve');
    Route::patch('/admin/caregivers/documents/{document}/reject', [CaregiverVerificationController::class, 'rejectDocument'])->name('admin.caregivers.documents.reject');

    Route::get('/admin/bookings/pengganti', [AdminBookingController::class, 'index'])->name('admin.bookings.replacement.index');
    Route::get('/admin/bookings/{booking}/pengganti', [AdminBookingController::class, 'show'])->name('admin.bookings.replacement.show');
    Route::patch('/admin/bookings/{booking}/pengganti', [AdminBookingController::class, 'assign'])->name('admin.bookings.replacement.assign');

    // Moderasi review dan penonaktifan akun
    Route::get('/admin/reviews', [ModerationController::class, 'reviewsIndex'])->name('admin.reviews.index');
    Route::patch('/admin/reviews/{review}/hide', [ModerationController::class, 'hideReview'])->name('admin.reviews.hide');
    Route::patch('/admin/reviews/{review}/unhide', [ModerationController::class, 'unhideReview'])->name('admin.reviews.unhide');

    Route::get('/admin/users', [ModerationController::class, 'usersIndex'])->name('admin.users.index');
    Route::patch('/admin/users/{user}/suspend', [ModerationController::class, 'suspendUser'])->name('admin.users.suspend');
    Route::patch('/admin/users/{user}/reactivate', [ModerationController::class, 'reactivateUser'])->name('admin.users.reactivate');

    // Pengaturan platform: kontak & bantuan, payment gateway, komisi & refund
    Route::get('/admin/settings', [SettingController::class, 'index'])->name('admin.settings.index');
    Route::patch('/admin/settings', [SettingController::class, 'update'])->name('admin.settings.update');

    // Papan pengumuman homepage (promo/pengumuman/iklan)
    Route::get('/admin/announcements', [AnnouncementController::class, 'index'])->name('admin.announcements.index');
    Route::get('/admin/announcements/create', [AnnouncementController::class, 'create'])->name('admin.announcements.create');
    Route::post('/admin/announcements', [AnnouncementController::class, 'store'])->name('admin.announcements.store');
    Route::get('/admin/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('admin.announcements.edit');
    Route::patch('/admin/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('admin.announcements.update');
    Route::patch('/admin/announcements/{announcement}/toggle', [AnnouncementController::class, 'toggle'])->name('admin.announcements.toggle');
    Route::delete('/admin/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('admin.announcements.destroy');
});

// Panel komplain staf (support/admin) + bantuan darurat (auth)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/help', [ComplaintController::class, 'help'])->name('help');
});

Route::middleware(['auth', 'verified', 'role:support,finance,admin'])->group(function () {
    Route::get('/complaints', [ComplaintController::class, 'staffIndex'])->name('complaints.staff.index');
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'staffShow'])->name('complaints.staff.show');
    Route::patch('/complaints/{complaint}', [ComplaintController::class, 'staffUpdate'])->name('complaints.staff.update');

    // Detail customer/pasien untuk kebutuhan penanganan (support, finance, admin)
    Route::get('/admin/customers/{customer}', [CustomerController::class, 'show'])->name('admin.customers.show');

    // Laporan transaksi & performa (UC-13)
    Route::get('/admin/reports', [ReportController::class, 'index'])->name('admin.reports.index');
});

Route::middleware(['auth', 'verified', 'role:support,admin'])->group(function () {
    // Audit log aktivitas penting
    Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');
});

// Pencairan dana caregiver: finance yang menangani, admin juga boleh (dashboard finance menautkannya)
Route::middleware(['auth', 'verified', 'role:finance,admin'])->group(function () {
    Route::get('/admin/payouts', [PayoutController::class, 'adminIndex'])->name('admin.payouts.index');
    Route::patch('/admin/payouts/{payout}/process', [PayoutController::class, 'process'])->name('admin.payouts.process');

    // Daftar transaksi & invoice (FR-22 / UC-13) untuk finance & admin
    Route::get('/admin/transactions', [TransactionController::class, 'index'])->name('admin.transactions.index');
    Route::get('/admin/transactions/{payment}/invoice', [TransactionController::class, 'invoice'])->name('admin.transactions.invoice');
});

// Aksi resolusi sengketa: refund & pembatalan payout (finance/admin)
Route::middleware(['auth', 'verified', 'role:finance,admin'])->group(function () {
    Route::post('/complaints/{complaint}/refund', [ComplaintController::class, 'resolveRefund'])->name('complaints.resolve.refund');
    Route::post('/complaints/{complaint}/payout-cancel', [ComplaintController::class, 'cancelPayout'])->name('complaints.resolve.payout-cancel');
});

require __DIR__.'/auth.php';
