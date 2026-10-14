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
                'required_if:status_ayah,hidup',
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
                'required_if:status_ibu,hidup',
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
                'required_if:memiliki_wali,true,1',
                'nullable',
                'string',
                'max:150',
            ],

            'nik_wali' => [
                'required_if:memiliki_wali,true,1',
                'nullable',
                'digits:16',
            ],

            'pekerjaan_wali' => [
                'required_if:memiliki_wali,true,1',
                'nullable',
                'string',
                'max:150',
            ],

            'penghasilan_wali' => [
                'required_if:memiliki_wali,true,1',
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],
        ], [
            'nik_ayah.required_if' => 'NIK Ayah wajib diisi 16 digit apabila status ayah hidup.',
            'nik_ayah.digits' => 'NIK Ayah harus terdiri dari 16 digit angka.',
            'nik_ibu.required_if' => 'NIK Ibu wajib diisi 16 digit apabila status ibu hidup.',
            'nik_ibu.digits' => 'NIK Ibu harus terdiri dari 16 digit angka.',
            'nama_wali.required_if' => 'Nama wali wajib diisi jika memiliki wali.',
            'nik_wali.required_if' => 'NIK Wali wajib diisi jika memiliki wali.',
            'nik_wali.digits' => 'NIK Wali harus terdiri dari 16 digit angka.',
            'pekerjaan_wali.required_if' => 'Pekerjaan wali wajib diisi jika memiliki wali.',
            'penghasilan_wali.required_if' => 'Penghasilan wali wajib diisi jika memiliki wali.',
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
