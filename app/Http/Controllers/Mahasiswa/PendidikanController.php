<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Models\Pendidikan;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PendidikanController extends Controller
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

        $pendaftaran->load(['pendidikan', 'periode', 'kategoriBeasiswa']);

        return view('mahasiswa.pendidikan.index', [
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

        $pendidikanId = $pendaftaran->pendidikan?->id;
        $currentYear = (int) now()->year;

        $validated = $request->validate([
            /*
            |--------------------------------------------------------------------------
            | DATA AKADEMIK MAHASISWA
            |--------------------------------------------------------------------------
            */

            'nim' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pendidikans', 'nim')->where(function ($query) use ($pendaftaran) {
                    $query->whereNotIn('pendaftaran_id', function ($sub) use ($pendaftaran) {
                        $sub->select('id')->from('pendaftarans')->where('user_id', $pendaftaran->user_id);
                    });
                }),
            ],

            'universitas' => [
                'required',
                'string',
                'max:150',
            ],

            'status_perguruan_tinggi' => [
                'required',
                Rule::in(['negeri', 'swasta']),
            ],

            'fakultas' => [
                'required',
                'string',
                'max:150',
            ],

            /*
            |--------------------------------------------------------------------------
            | Jurusan wajib.
            |--------------------------------------------------------------------------
            | Program Studi boleh kosong karena ada perguruan tinggi
            | yang tidak memiliki struktur Program Studi.
            |--------------------------------------------------------------------------
            */

            'jurusan' => [
                'required',
                'string',
                'max:150',
            ],

            'program_studi' => [
                'nullable',
                'string',
                'max:150',
            ],

            'jenjang' => [
                'required',
                Rule::in([
                    'D3',
                    'D4',
                    'S1',
                    'S2',
                    'S3',
                ]),
            ],

            'semester' => [
                'required',
                'integer',
                'between:1,14',
            ],

            'ipk' => [
                'required',
                'numeric',
                'between:0,4',
            ],

            'nominal_ukt' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'tahun_masuk' => [
                'required',
                'integer',
                'digits:4',
                'between:1990,'.$currentYear,
            ],

            'akreditasi_perguruan_tinggi' => [
                'required',
                'string',
                'max:50',
            ],

            'alamat_perguruan_tinggi' => [
                'required',
                'string',
                'max:1000',
            ],

        ], [

            /*
            |--------------------------------------------------------------------------
            | PESAN ERROR
            |--------------------------------------------------------------------------
            */

            'nim.required' => 'NIM wajib diisi.',

            'nim.max' => 'NIM maksimal 50 karakter.',

            'nim.unique' => 'NIM ini sudah terdaftar pada akun mahasiswa lain. Pastikan penulisan NIM sudah benar atau gunakan akun Anda yang sebelumnya.',

            'universitas.required' => 'Nama perguruan tinggi wajib diisi.',

            'status_perguruan_tinggi.required' => 'Status perguruan tinggi wajib dipilih.',

            'status_perguruan_tinggi.in' => 'Status perguruan tinggi tidak valid.',

            'fakultas.required' => 'Fakultas wajib diisi.',

            'jurusan.required' => 'Jurusan wajib diisi.',

            'jenjang.required' => 'Jenjang pendidikan wajib dipilih.',

            'jenjang.in' => 'Jenjang pendidikan tidak valid.',

            'semester.required' => 'Semester wajib diisi.',

            'semester.integer' => 'Semester harus berupa angka.',

            'semester.between' => 'Semester harus berada antara 1 sampai 14.',

            'ipk.required' => 'Indeks Prestasi Total wajib diisi.',

            'ipk.numeric' => 'Indeks Prestasi Total harus berupa angka.',

            'ipk.between' => 'Indeks Prestasi Total harus berada pada rentang 0,00 sampai 4,00.',

            'nominal_ukt.required' => 'Besaran nilai UKT wajib diisi.',

            'nominal_ukt.numeric' => 'Besaran nilai UKT harus berupa angka.',

            'nominal_ukt.min' => 'Besaran nilai UKT tidak boleh kurang dari 0.',

            'tahun_masuk.required' => 'Tahun masuk wajib diisi.',

            'tahun_masuk.digits' => 'Tahun masuk harus terdiri dari 4 digit.',

            'tahun_masuk.between' => 'Tahun masuk tidak valid.',

            'akreditasi_perguruan_tinggi.required' => 'Akreditasi perguruan tinggi wajib diisi.',

            'alamat_perguruan_tinggi.required' => 'Alamat perguruan tinggi wajib diisi.',

        ]);

        $pendaftaran->loadMissing('kategoriBeasiswa');

        DB::transaction(function () use ($pendaftaran, $validated): void {

            Pendidikan::query()->updateOrCreate(
                [
                    'pendaftaran_id' => $pendaftaran->id,
                ],
                $validated
            );

            /*
            |--------------------------------------------------------------------------
            | Jika data pendidikan berubah setelah review,
            | review harus dikonfirmasi ulang.
            |--------------------------------------------------------------------------
            */

            $pendaftaran
                ->forceFill([
                    'review_dikonfirmasi_at' => null,
                ])
                ->save();
        });

        $nextRoute = $this->flow->requiresPrestasi($pendaftaran)
            ? 'mahasiswa.prestasi.index'
            : 'mahasiswa.orang-tua.index';

        return redirect()
            ->route($nextRoute)
            ->with(
                'success',
                'Data pendidikan berhasil disimpan.'
            );
    }
}
