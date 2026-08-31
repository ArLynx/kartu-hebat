<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\DataPribadi;
use App\Models\Village;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DataPribadiController extends Controller
{
    public function __construct(private readonly MahasiswaPendaftaranService $flow)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        if (! $pendaftaran) {
            return redirect()
                ->route('mahasiswa.pendaftaran.create')
                ->with('error', 'Buat pendaftaran beasiswa terlebih dahulu.');
        }

        $pendaftaran->load([
            'dataPribadi.village.kecamatan.kabupaten',
            'periode',
            'kategoriBeasiswa',
        ]);

        $villages = Village::query()
            ->with(['kecamatan.kabupaten'])
            ->orderBy('name')
            ->get()
            ->sortBy(
                fn (Village $village): string => implode('|', [
                    $village->kabupaten?->name,
                    $village->kecamatan?->name,
                    $village->name,
                ]),
                SORT_NATURAL | SORT_FLAG_CASE
            )
            ->values();

        return view('mahasiswa.data-pribadi.index', [
            'pendaftaran' => $pendaftaran,
            'villages' => $villages,
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

        $dataPribadiId = $pendaftaran->dataPribadi?->id;

        $validated = $request->validate([
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('data_pribadis', 'nik')->ignore($dataPribadiId),
            ],

            'nomor_rekening' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
            ],

            'nama_lengkap' => [
                'required',
                'string',
                'max:150',
            ],

            'tempat_lahir' => [
                'required',
                'string',
                'max:100',
            ],

            'tanggal_lahir' => [
                'required',
                'date',
                'before:today',
            ],

            'jenis_kelamin' => [
                'required',
                Rule::in(['L', 'P']),
            ],

            'agama' => [
                'required',
                'string',
                'max:30',
            ],

            'no_hp' => [
                'required',
                'string',
                'max:25',
            ],

            'alamat' => [
                'required',
                'string',
                'max:2000',
            ],

            'provinsi' => [
                'required',
                'string',
                'max:100',
            ],

            'village_id' => [
                'required',
                'integer',
                Rule::exists('villages', 'id'),
            ],

            'kode_pos' => [
                'required',
                'string',
                'max:10',
            ],
        ], [
            'nik.required' =>
                'NIK wajib diisi.',

            'nik.digits' =>
                'NIK harus terdiri dari 16 digit.',

            'nik.unique' =>
                'NIK tersebut sudah digunakan pada pendaftaran lain.',

            'nomor_rekening.required' =>
                'Nomor rekening Bank Kalteng wajib diisi.',

            'nomor_rekening.regex' =>
                'Nomor rekening Bank Kalteng hanya boleh berisi angka.',

            'nama_lengkap.required' =>
                'Nama lengkap wajib diisi.',

            'nama_lengkap.max' =>
                'Nama lengkap maksimal 150 karakter.',

            'tempat_lahir.required' =>
                'Tempat lahir wajib diisi.',

            'tanggal_lahir.required' =>
                'Tanggal lahir wajib diisi.',

            'tanggal_lahir.before' =>
                'Tanggal lahir harus merupakan tanggal yang sudah lewat.',

            'jenis_kelamin.required' =>
                'Jenis kelamin wajib dipilih.',

            'jenis_kelamin.in' =>
                'Jenis kelamin tidak valid.',

            'agama.required' =>
                'Agama wajib dipilih.',

            'no_hp.required' =>
                'Nomor HP/WhatsApp wajib diisi.',

            'alamat.required' =>
                'Alamat lengkap wajib diisi.',

            'provinsi.required' =>
                'Provinsi wajib diisi.',

            'village_id.required' =>
                'Desa/Kelurahan wajib dipilih.',

            'village_id.exists' =>
                'Desa/Kelurahan yang dipilih tidak valid.',

            'kode_pos.required' =>
                'Kode pos wajib diisi.',
        ]);

        $village = Village::query()
            ->with(['kecamatan.kabupaten'])
            ->findOrFail($validated['village_id']);

        $validated['kabupaten'] = $village->kabupaten->name;
        $validated['kecamatan'] = $village->kecamatan->name;
        $validated['desa'] = $village->name;

        DB::transaction(function () use (
            $pendaftaran,
            $validated,
            $village,
            $request
        ): void {
            DataPribadi::query()->updateOrCreate(
                [
                    'pendaftaran_id' => $pendaftaran->id,
                ],
                $validated
            );

            $request->user()->forceFill([
                'village_id' => $village->id,
                'kecamatan_id' => $village->kecamatan_id,
                'kabupaten_id' => $village->kabupaten_id,
            ])->save();

            $pendaftaran
                ->forceFill([
                    'review_dikonfirmasi_at' => null,
                ])
                ->save();
        });

        return redirect()
            ->route('mahasiswa.pendidikan.index')
            ->with(
                'success',
                'Data pribadi berhasil disimpan.'
            );
    }
}