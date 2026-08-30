<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\MahasiswaProfile;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
        config(['kartu_hebat.document_disk' => 'local']);
    }

    public function test_authorized_user_can_preview_pdf_inline(): void
    {
        [$student, $document] = $this->createApplicationWithDocument();

        $response = $this->actingAs($student)
            ->get(route('documents.preview', $document));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringStartsWith(
            'inline;',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_authorized_user_can_preview_png_inline(): void
    {
        [$student, $document] = $this->createApplicationWithDocument('image/png', 'kartu-mahasiswa.png');

        $response = $this->actingAs($student)
            ->get(route('documents.preview', $document));

        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith(
            'inline;',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_authorized_operator_can_preview_document_inline(): void
    {
        [, $document, $village] = $this->createApplicationWithDocument();
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_DESA,
            'village_id' => $village->id,
            'kecamatan_id' => $village->kecamatan_id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        $response = $this->actingAs($operator)
            ->get(route('documents.preview', $document));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith(
            'inline;',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_operator_without_two_factor_is_redirected_before_document_access(): void
    {
        [, $document, $village] = $this->createApplicationWithDocument();
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_DESA,
            'village_id' => $village->id,
            'kecamatan_id' => $village->kecamatan_id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        $this->actingAs($operator)
            ->get(route('documents.preview', $document))
            ->assertRedirect(route('2fa.setup'));
    }

    public function test_download_endpoint_keeps_attachment_disposition(): void
    {
        [$student, $document] = $this->createApplicationWithDocument();

        $response = $this->actingAs($student)
            ->get(route('documents.download', $document));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith(
            'attachment;',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_operator_outside_students_region_cannot_preview_document(): void
    {
        [, $document, $studentVillage] = $this->createApplicationWithDocument();
        $otherVillage = Village::query()
            ->where('id', '!=', $studentVillage->id)
            ->firstOrFail();

        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_DESA,
            'village_id' => $otherVillage->id,
            'kecamatan_id' => $otherVillage->kecamatan_id,
            'kabupaten_id' => $otherVillage->kabupaten_id,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->actingAs($operator)
            ->get(route('documents.preview', $document))
            ->assertForbidden();
    }

    public function test_non_previewable_document_returns_unsupported_media_type(): void
    {
        [$student, $document] = $this->createApplicationWithDocument('text/plain', 'catatan.txt');

        $this->actingAs($student)
            ->get(route('documents.preview', $document))
            ->assertStatus(415);
    }

    private function createApplicationWithDocument(
        string $mimeType = 'application/pdf',
        string $originalName = 'kartu-keluarga.pdf',
    ): array {
        $village = Village::query()->firstOrFail();
        $student = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'village_id' => $village->id,
            'kecamatan_id' => $village->kecamatan_id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'nik' => '6212010101010099',
            'nim' => 'KHM-PREVIEW-001',
            'phone' => '081200000099',
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.50,
            'alamat' => 'Alamat pengujian preview',
            'village_id' => $village->id,
        ]);

        $application = Application::query()->create([
            'nomor_pengajuan' => 'KHM-PREVIEW-0001',
            'mahasiswa_id' => $student->id,
            'periode' => config('kartu_hebat.current_period'),
            'application_type' => ApplicationType::AKADEMIK,
            'status' => ApplicationStatus::VERIFIKASI_DINAS,
        ]);

        $path = 'applications/'.$application->id.'/KK/document-preview';
        Storage::disk('local')->put($path, 'document-content');

        $document = Document::query()->create([
            'application_id' => $application->id,
            'document_type_id' => DocumentType::query()->where('code', 'KK')->firstOrFail()->id,
            'uploaded_by' => $student->id,
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => strlen('document-content'),
            'checksum' => hash('sha256', 'document-content'),
        ]);

        return [$student, $document, $village];
    }
}
