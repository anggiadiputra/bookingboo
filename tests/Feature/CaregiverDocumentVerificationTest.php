<?php

namespace Tests\Feature;

use App\Models\Caregiver;
use App\Models\CaregiverDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CaregiverDocumentVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    private function makeCaregiverUser(): User
    {
        return User::factory()->create([
            'role' => 'caregiver',
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    private function makeCaregiver(string $verificationStatus = 'pending'): Caregiver
    {
        $user = $this->makeCaregiverUser();

        return Caregiver::create([
            'user_id' => $user->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'rating' => 4.8,
            'verification_status' => $verificationStatus,
            'bio' => 'Caregiver berpengalaman.',
        ]);
    }

    public function test_caregiver_can_view_documents_page(): void
    {
        $user = $this->makeCaregiverUser();
        Caregiver::create([
            'user_id' => $user->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'rating' => 4.8,
            'verification_status' => 'pending',
            'bio' => 'Caregiver berpengalaman.',
        ]);

        $response = $this->actingAs($user)->get('/caregiver/documents');

        $response->assertStatus(200);
        $response->assertSee('Dokumen Verifikasi');
    }

    public function test_caregiver_can_upload_document(): void
    {
        Storage::fake('public');
        $user = $this->makeCaregiverUser();
        $caregiver = Caregiver::create([
            'user_id' => $user->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'rating' => 4.8,
            'verification_status' => 'pending',
            'bio' => 'Caregiver berpengalaman.',
        ]);

        $response = $this->actingAs($user)->post('/caregiver/documents', [
            'type' => CaregiverDocument::TYPE_KTP,
            'document' => UploadedFile::fake()->image('ktp.jpg'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('caregiver_documents', [
            'caregiver_id' => $caregiver->id,
            'type' => CaregiverDocument::TYPE_KTP,
            'status' => CaregiverDocument::STATUS_PENDING,
        ]);
    }

    public function test_admin_can_view_caregiver_list(): void
    {
        $admin = $this->makeAdmin();
        $this->makeCaregiver();

        $response = $this->actingAs($admin)->get('/admin/caregivers');

        $response->assertStatus(200);
        $response->assertSee('Verifikasi Caregiver');
    }

    public function test_admin_can_approve_document_and_verify_caregiver(): void
    {
        $admin = $this->makeAdmin();
        $caregiver = $this->makeCaregiver();
        $document = CaregiverDocument::create([
            'caregiver_id' => $caregiver->id,
            'type' => CaregiverDocument::TYPE_KTP,
            'file_path' => 'documents/ktp.jpg',
            'status' => CaregiverDocument::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->patch("/admin/caregivers/documents/{$document->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('caregiver_documents', [
            'id' => $document->id,
            'status' => CaregiverDocument::STATUS_APPROVED,
        ]);
        $this->assertNotNull($document->fresh()->reviewed_at);
        $this->assertDatabaseHas('caregivers', [
            'id' => $caregiver->id,
            'verification_status' => Caregiver::VERIFICATION_VERIFIED,
        ]);
    }

    public function test_admin_can_reject_document(): void
    {
        $admin = $this->makeAdmin();
        $caregiver = $this->makeCaregiver();
        $document = CaregiverDocument::create([
            'caregiver_id' => $caregiver->id,
            'type' => CaregiverDocument::TYPE_STR,
            'file_path' => 'documents/str.jpg',
            'status' => CaregiverDocument::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->patch("/admin/caregivers/documents/{$document->id}/reject", [
            'rejection_reason' => 'Dokumen tidak jelas',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('caregiver_documents', [
            'id' => $document->id,
            'status' => CaregiverDocument::STATUS_REJECTED,
            'rejection_reason' => 'Dokumen tidak jelas',
        ]);
        $this->assertDatabaseHas('caregivers', [
            'id' => $caregiver->id,
            'verification_status' => Caregiver::VERIFICATION_REJECTED,
        ]);
    }

    public function test_admin_can_approve_caregiver_directly(): void
    {
        $admin = $this->makeAdmin();
        $caregiver = $this->makeCaregiver('pending');

        $response = $this->actingAs($admin)->patch("/admin/caregivers/{$caregiver->user->public_id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('caregivers', [
            'id' => $caregiver->id,
            'verification_status' => Caregiver::VERIFICATION_VERIFIED,
        ]);
    }

    public function test_admin_can_reject_caregiver_directly(): void
    {
        $admin = $this->makeAdmin();
        $caregiver = $this->makeCaregiver('pending');

        $response = $this->actingAs($admin)->patch("/admin/caregivers/{$caregiver->user->public_id}/reject");

        $response->assertRedirect();
        $this->assertDatabaseHas('caregivers', [
            'id' => $caregiver->id,
            'verification_status' => Caregiver::VERIFICATION_REJECTED,
        ]);
    }

    public function test_upload_replaces_pending_or_rejected_document_of_same_type(): void
    {
        Storage::fake('public');
        $caregiver = $this->makeCaregiver();
        $user = $caregiver->user;

        $old = $caregiver->documents()->create([
            'type' => CaregiverDocument::TYPE_KTP,
            'file_path' => 'caregiver-documents/old.jpg',
            'status' => CaregiverDocument::STATUS_REJECTED,
            'rejection_reason' => 'Tidak terbaca',
        ]);

        $response = $this->actingAs($user)->post('/caregiver/documents', [
            'type' => CaregiverDocument::TYPE_KTP,
            'document' => UploadedFile::fake()->image('ktp.jpg'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('caregiver_documents', ['id' => $old->id]);
        Storage::disk('public')->assertMissing('caregiver-documents/old.jpg');
        $this->assertDatabaseHas('caregiver_documents', [
            'type' => CaregiverDocument::TYPE_KTP,
            'status' => CaregiverDocument::STATUS_PENDING,
        ]);
    }

    public function test_upload_does_not_replace_approved_document_of_same_type(): void
    {
        Storage::fake('public');
        $caregiver = $this->makeCaregiver();
        $user = $caregiver->user;

        $approved = $caregiver->documents()->create([
            'type' => CaregiverDocument::TYPE_KTP,
            'file_path' => 'caregiver-documents/approved.jpg',
            'status' => CaregiverDocument::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($user)->post('/caregiver/documents', [
            'type' => CaregiverDocument::TYPE_KTP,
            'document' => UploadedFile::fake()->image('ktp-v2.jpg'),
        ]);

        $this->assertDatabaseHas('caregiver_documents', ['id' => $approved->id]);
        $this->assertDatabaseCount('caregiver_documents', 2);
    }

    public function test_reupload_of_rejected_document_resets_verification_to_pending(): void
    {
        Storage::fake('public');
        $caregiver = $this->makeCaregiver(Caregiver::VERIFICATION_REJECTED);
        $user = $caregiver->user;

        $caregiver->documents()->create([
            'type' => CaregiverDocument::TYPE_KTP,
            'file_path' => 'caregiver-documents/old.jpg',
            'status' => CaregiverDocument::STATUS_REJECTED,
        ]);

        $this->actingAs($user)->post('/caregiver/documents', [
            'type' => CaregiverDocument::TYPE_KTP,
            'document' => UploadedFile::fake()->image('ktp.jpg'),
        ]);

        $this->assertSame(Caregiver::VERIFICATION_PENDING, $caregiver->fresh()->verification_status);
    }

    public function test_caregiver_cannot_delete_other_caregiver_document(): void
    {
        Storage::fake('public');
        $caregiver = $this->makeCaregiver();
        $otherCaregiver = $this->makeCaregiver();
        $other = $otherCaregiver->documents()->create([
            'type' => CaregiverDocument::TYPE_KTP,
            'file_path' => 'caregiver-documents/other.jpg',
            'status' => CaregiverDocument::STATUS_PENDING,
        ]);

        $this->actingAs($caregiver->user)->delete("/caregiver/documents/{$other->id}")->assertForbidden();
    }

    public function test_reject_document_requires_reason(): void
    {
        $admin = $this->makeAdmin();
        $caregiver = $this->makeCaregiver();
        $document = CaregiverDocument::create([
            'caregiver_id' => $caregiver->id,
            'type' => CaregiverDocument::TYPE_KTP,
            'file_path' => 'documents/ktp.jpg',
            'status' => CaregiverDocument::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->from('/admin/caregivers/'.$caregiver->user->public_id)
            ->patch("/admin/caregivers/documents/{$document->id}/reject", [])
            ->assertSessionHasErrors('rejection_reason');
    }

    public function test_non_admin_cannot_access_caregiver_verification(): void
    {
        $support = User::factory()->create([
            'role' => 'support',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->actingAs($support)->get('/admin/caregivers')->assertForbidden();
    }

    public function test_non_caregiver_cannot_access_document_page(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->actingAs($customer)->get('/caregiver/documents')->assertForbidden();
    }
}
