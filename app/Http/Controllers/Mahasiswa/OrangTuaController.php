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

        $memilikiWali = $request->boolean('memiliki_wali');

        $request->merge([
            'memiliki_wali' => $memilikiWali,
            'pekerjaan_wali' => $memilikiWali ? $request->input('pekerjaan_wali') : null,
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
                'required_if:status_ayah,hidup',
                'nullable',
                'string',
                'max:150',
            ],

            'pekerjaan_ayah_lainnya' => [
                'required_if:pekerjaan_ayah,Lainnya',
                'nullable',
                'string',
                'max:150',
            ],

            'jabatan_ayah' => [
                'required_if:pekerjaan_ayah,ASN',
                'nullable',
                'string',
                'max:150',
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
                'required_if:status_ibu,hidup',
                'nullable',
                'string',
                'max:150',
            ],

            'pekerjaan_ibu_lainnya' => [
                'required_if:pekerjaan_ibu,Lainnya',
                'nullable',
                'string',
                'max:150',
            ],

            'jabatan_ibu' => [
                'required_if:pekerjaan_ibu,ASN',
                'nullable',
                'string',
                'max:150',
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

            'pekerjaan_wali_lainnya' => [
                'required_if:pekerjaan_wali,Lainnya',
                'nullable',
                'string',
                'max:150',
            ],

            'jabatan_wali' => [
                'required_if:pekerjaan_wali,ASN',
                'nullable',
                'string',
                'max:150',
            ],
        ], [
            'nik_ayah.required_if' => 'NIK Ayah wajib diisi 16 digit apabila status ayah hidup.',
            'nik_ayah.digits' => 'NIK Ayah harus terdiri dari 16 digit angka.',
            'pekerjaan_ayah.required_if' => 'Pekerjaan Ayah wajib dipilih apabila status ayah hidup.',
            'pekerjaan_ayah_lainnya.required_if' => 'Nama pekerjaan Ayah wajib diisi apabila memilih Lainnya.',
            'jabatan_ayah.required_if' => 'Jabatan Ayah wajib diisi apabila memilih pekerjaan ASN.',

            'nik_ibu.required_if' => 'NIK Ibu wajib diisi 16 digit apabila status ibu hidup.',
            'nik_ibu.digits' => 'NIK Ibu harus terdiri dari 16 digit angka.',
            'pekerjaan_ibu.required_if' => 'Pekerjaan Ibu wajib dipilih apabila status ibu hidup.',
            'pekerjaan_ibu_lainnya.required_if' => 'Nama pekerjaan Ibu wajib diisi apabila memilih Lainnya.',
            'jabatan_ibu.required_if' => 'Jabatan Ibu wajib diisi apabila memilih pekerjaan ASN.',

            'nama_wali.required_if' => 'Nama wali wajib diisi jika memiliki wali.',
            'nik_wali.required_if' => 'NIK Wali wajib diisi jika memiliki wali.',
            'nik_wali.digits' => 'NIK Wali harus terdiri dari 16 digit angka.',
            'pekerjaan_wali.required_if' => 'Pekerjaan wali wajib dipilih jika memiliki wali.',
            'pekerjaan_wali_lainnya.required_if' => 'Nama pekerjaan Wali wajib diisi apabila memilih Lainnya.',
            'jabatan_wali.required_if' => 'Jabatan Wali wajib diisi apabila memilih pekerjaan ASN.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Normalisasi Pekerjaan & Jabatan
        |--------------------------------------------------------------------------
        */

        if (($validated['pekerjaan_ayah'] ?? null) === 'Lainnya') {
            $validated['pekerjaan_ayah'] = trim((string) ($validated['pekerjaan_ayah_lainnya'] ?? ''));
        }
        if (($validated['pekerjaan_ayah'] ?? null) !== 'ASN') {
            $validated['jabatan_ayah'] = null;
        }

        if (($validated['pekerjaan_ibu'] ?? null) === 'Lainnya') {
            $validated['pekerjaan_ibu'] = trim((string) ($validated['pekerjaan_ibu_lainnya'] ?? ''));
        }
        if (($validated['pekerjaan_ibu'] ?? null) !== 'ASN') {
            $validated['jabatan_ibu'] = null;
        }

        if (! $validated['memiliki_wali']) {
            $validated = array_merge($validated, [
                'nama_wali' => null,
                'nik_wali' => null,
                'pekerjaan_wali' => null,
                'jabatan_wali' => null,
            ]);
        } else {
            if (($validated['pekerjaan_wali'] ?? null) === 'Lainnya') {
                $validated['pekerjaan_wali'] = trim((string) ($validated['pekerjaan_wali_lainnya'] ?? ''));
            }
            if (($validated['pekerjaan_wali'] ?? null) !== 'ASN') {
                $validated['jabatan_wali'] = null;
            }
        }

        unset(
            $validated['pekerjaan_ayah_lainnya'],
            $validated['pekerjaan_ibu_lainnya'],
            $validated['pekerjaan_wali_lainnya'],
        );

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
