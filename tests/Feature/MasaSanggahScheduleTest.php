<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasaSanggahScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_save_masa_sanggah_schedule_and_see_it_when_reopening_the_form(): void
    {
        $superadmin = $this->superadmin();
        $periode = $this->periode();

        $response = $this->actingAs($superadmin)->put(route('superadmin.periodes.update', $periode), [
            'tahun' => $periode->tahun,
            'nama' => $periode->nama,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
            'status' => 'aktif',
        ]);

        $response->assertRedirect(route('superadmin.periodes.edit', $periode));

        $periode->refresh();
        $this->assertSame('2026-11-10', $periode->tanggal_mulai_sanggah?->format('Y-m-d'));
        $this->assertSame('2026-11-12', $periode->tanggal_selesai_sanggah?->format('Y-m-d'));

        $this->actingAs($superadmin)
            ->get(route('superadmin.periodes.edit', $periode))
            ->assertOk()
            ->assertSee('2026-11-10')
            ->assertSee('2026-11-12');
    }

    public function test_masa_sanggah_end_date_must_not_precede_start_date(): void
    {
        $superadmin = $this->superadmin();
        $periode = $this->periode();

        $response = $this->actingAs($superadmin)->put(route('superadmin.periodes.update', $periode), [
            'tahun' => $periode->tahun,
            'nama' => $periode->nama,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-11-12',
            'tanggal_selesai_sanggah' => '2026-11-10',
            'status' => 'aktif',
        ]);

        $response->assertSessionHasErrors([
            'tanggal_selesai_sanggah' => 'Tanggal selesai masa sanggah harus sama atau setelah tanggal mulai masa sanggah.',
        ]);

        $periode->refresh();
        $this->assertNull($periode->tanggal_mulai_sanggah);
        $this->assertNull($periode->tanggal_selesai_sanggah);
    }

    public function test_masa_sanggah_dates_are_optional_and_a_saved_schedule_can_be_cleared(): void
    {
        $superadmin = $this->superadmin();
        $periode = $this->periode([
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
        ]);

        $response = $this->actingAs($superadmin)->put(route('superadmin.periodes.update', $periode), [
            'tahun' => $periode->tahun,
            'nama' => $periode->nama,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '',
            'tanggal_selesai_sanggah' => '',
            'status' => 'aktif',
        ]);

        $response->assertRedirect(route('superadmin.periodes.edit', $periode));

        $periode->refresh();
        $this->assertNull($periode->tanggal_mulai_sanggah);
        $this->assertNull($periode->tanggal_selesai_sanggah);

        $this->actingAs($superadmin)
            ->get(route('superadmin.periodes.edit', $periode))
            ->assertOk()
            ->assertDontSee('2026-11-10')
            ->assertDontSee('2026-11-12');

        $this->actingAs($superadmin)
            ->get(route('superadmin.periodes.index'))
            ->assertOk()
            ->assertSee('Belum diatur')
            ->assertSee('Belum dijadwalkan');
    }

    public function test_masa_sanggah_schedule_requires_both_dates_when_one_is_filled(): void
    {
        $superadmin = $this->superadmin();
        $periode = $this->periode();

        $response = $this->actingAs($superadmin)->put(route('superadmin.periodes.update', $periode), [
            'tahun' => $periode->tahun,
            'nama' => $periode->nama,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'status' => 'aktif',
        ]);

        $response->assertSessionHasErrors('tanggal_selesai_sanggah');

        $response = $this->actingAs($superadmin)->put(route('superadmin.periodes.update', $periode), [
            'tahun' => $periode->tahun,
            'nama' => $periode->nama,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_selesai_sanggah' => '2026-11-12',
            'status' => 'aktif',
        ]);

        $response->assertSessionHasErrors('tanggal_mulai_sanggah');

        $periode->refresh();
        $this->assertNull($periode->tanggal_mulai_sanggah);
        $this->assertNull($periode->tanggal_selesai_sanggah);
    }

    public function test_periode_list_shows_masa_sanggah_status_for_today(): void
    {
        $superadmin = $this->superadmin();

        $this->periode(['tahun' => 2030, 'nama' => 'Belum Dijadwalkan']);
        $this->periode(['tahun' => 2029, 'nama' => 'Belum Dimulai', 'tanggal_mulai_sanggah' => '2026-11-20', 'tanggal_selesai_sanggah' => '2026-11-25']);
        $this->periode(['tahun' => 2028, 'nama' => 'Sedang Berlangsung', 'tanggal_mulai_sanggah' => '2026-11-01', 'tanggal_selesai_sanggah' => '2026-11-30']);
        $this->periode(['tahun' => 2027, 'nama' => 'Telah Berakhir', 'tanggal_mulai_sanggah' => '2026-10-01', 'tanggal_selesai_sanggah' => '2026-10-31']);

        $this->travelTo('2026-11-15');

        $this->actingAs($superadmin)
            ->get(route('superadmin.periodes.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Belum Dijadwalkan',
                'Belum dijadwalkan',
                'Belum Dimulai',
                'Belum dimulai',
                'Sedang Berlangsung',
                'Sedang berlangsung',
                'Telah Berakhir',
                'Telah berakhir',
            ]);
    }

    public function test_masa_sanggah_status_is_inclusive_on_both_boundary_dates(): void
    {
        $superadmin = $this->superadmin();
        $this->periode([
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
        ]);

        foreach (['2026-11-10', '2026-11-11', '2026-11-12'] as $today) {
            $this->travelTo($today);

            $this->actingAs($superadmin)
                ->get(route('superadmin.periodes.index'))
                ->assertOk()
                ->assertSee('Sedang berlangsung')
                ->assertDontSee('Belum dimulai')
                ->assertDontSee('Telah berakhir');
        }

        $this->travelTo('2026-11-13');

        $this->actingAs($superadmin)
            ->get(route('superadmin.periodes.index'))
            ->assertOk()
            ->assertSee('Telah berakhir')
            ->assertDontSee('Sedang berlangsung');
    }

    private function periode(array $attributes = []): Periode
    {
        return Periode::query()->create(array_merge([
            'tahun' => 2026,
            'nama' => 'Periode Beasiswa 2026',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ], $attributes));
    }

    private function superadmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
