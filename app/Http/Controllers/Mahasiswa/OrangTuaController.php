<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\OrangTua;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrangTuaController extends Controller
{
    public function __construct(private readonly MahasiswaPendaftaranService $flow) {}

    public function index(Request $request): View|RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        if (! $pendaftaran) {
            return redirect()
                ->route('mahasiswa.pendaftaran.create')
                ->with('error', 'Buat pendaftaran beasiswa terlebih dahulu.');
        }

        $pendaftaran->load('orangTua');

        return view('mahasiswa.orang-tua.index', [
            'pendaftaran' => $pendaftaran,
            'stepStatuses' => $this->flow->completion($pendaftaran),
            'canEdit' => $this->flow->isEditable($pendaftaran),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        abort_unless($pendaftaran, 404);

        abort_unless(
            $this->flow->isEditable($pendaftaran),
            403,
            'Pendaftaran tidak dapat diubah.'
        );

        $request->merge([
            'memiliki_wali' => $request->boolean('memiliki_wali'),
        ]);

        $validated = $request->validate([
            /*
            |--------------------------------------------------------------------------
            | Ayah
            |--------------------------------------------------------------------------
            */
            'nama_ayah' => [
                'required',
                'string',
                'max:150',
            ],

            'status_ayah' => [
                'required',
                'in:hidup,meninggal_dunia',
            ],

            'nik_ayah' => [
                'nullable',
                'digits:16',
            ],

            'pekerjaan_ayah' => [
                'required',
                'string',
                'max:150',
            ],

            'penghasilan_ayah' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            /*
            |--------------------------------------------------------------------------
            | Ibu
            |--------------------------------------------------------------------------
            */
            'nama_ibu' => [
                'required',
                'string',
                'max:150',
            ],

            'status_ibu' => [
                'required',
                'in:hidup,meninggal_dunia',
            ],

            'nik_ibu' => [
                'nullable',
                'digits:16',
            ],

            'pekerjaan_ibu' => [
                'required',
                'string',
                'max:150',
            ],

            'penghasilan_ibu' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            /*
            |--------------------------------------------------------------------------
            | Wali
            |--------------------------------------------------------------------------
            */
            'memiliki_wali' => [
                'required',
                'boolean',
            ],

            'nama_wali' => [
                'nullable',
                'required_if:memiliki_wali,1',
                'string',
                'max:150',
            ],

            'nik_wali' => [
                'nullable',
                'required_if:memiliki_wali,1',
                'digits:16',
            ],

            'pekerjaan_wali' => [
                'nullable',
                'required_if:memiliki_wali,1',
                'string',
                'max:150',
            ],

            'penghasilan_wali' => [
                'nullable',
                'required_if:memiliki_wali,1',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Jika tidak menggunakan wali
        |--------------------------------------------------------------------------
        */

        if (! $validated['memiliki_wali']) {
            $validated = array_merge($validated, [
                'nama_wali' => null,
                'nik_wali' => null,
                'pekerjaan_wali' => null,
                'penghasilan_wali' => null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($pendaftaran, $validated): void {
            OrangTua::query()->updateOrCreate(
                [
                    'pendaftaran_id' => $pendaftaran->id,
                ],
                $validated,
            );

            /*
            |--------------------------------------------------------------------------
            | Jika data diubah, review harus dikonfirmasi ulang
            |--------------------------------------------------------------------------
            */

            $pendaftaran
                ->forceFill([
                    'review_dikonfirmasi_at' => null,
                ])
                ->save();
        });

        return redirect()
            ->route('mahasiswa.dokumen.index')
            ->with(
                'success',
                'Data orang tua/wali berhasil disimpan.'
            );
    }
}
