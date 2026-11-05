<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\SanggahanStatus;
use App\Enums\UserRole;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    use Auditable;

    protected static function booted(): void
    {
        static::saved(function (Application $application): void {
            if (
                ! $application->pendaftaran_id
                || (! $application->wasRecentlyCreated && ! $application->wasChanged('status'))
            ) {
                return;
            }

            $status = match ($application->status) {
                ApplicationStatus::DRAFT => 'draft',
                ApplicationStatus::DITERIMA => 'approved',
                ApplicationStatus::TMS, ApplicationStatus::DITOLAK => 'rejected',
                default => 'verification',
            };

            $application->pendaftaran()->update([
                'status' => $status,
                'submitted_at' => $application->submitted_at,
            ]);
        });
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'application_type' => ApplicationType::class,
            'submitted_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function documentVerifications(): HasMany
    {
        return $this->hasMany(DocumentVerification::class);
    }

    public function villageVerification(): HasOne
    {
        return $this->hasOne(VillageVerification::class);
    }

    public function districtVerification(): HasOne
    {
        return $this->hasOne(DistrictVerification::class);
    }

    public function agencyVerifications(): HasMany
    {
        return $this->hasMany(AgencyVerification::class);
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(VerificationLog::class)->orderByDesc('created_at');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ApplicationScore::class);
    }

    public function selection(): HasOne
    {
        return $this->hasOne(Selection::class);
    }

    public function sanggahan(): HasOne
    {
        return $this->hasOne(Sanggahan::class);
    }

    /**
     * Apakah application ini pernah mendapat sanggahan yang dikabulkan.
     */
    public function hasGrantedSanggahan(): bool
    {
        return $this->sanggahan?->status === SanggahanStatus::DIKABULKAN;
    }

    /**
     * Application dibuka kembali untuk dinas hanya bila sanggahannya
     * dikabulkan dan periode application masih aktif.
     */
    public function isReopenedForVerification(): bool
    {
        return $this->hasGrantedSanggahan()
            && $this->resolvedPeriode()?->status === 'aktif';
    }

    /**
     * Antrean verifikasi: application pada periode aktif aplikasi, ditambah
     * application periode lain yang dibuka kembali lewat sanggahan.
     */
    public function scopeWhereOpenForVerification(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('applications.periode', config('kartu_hebat.current_period'))
                ->orWhere(function (Builder $query): void {
                    $query
                        ->whereHas('sanggahan', fn (Builder $sanggahan) => $sanggahan
                            ->where('status', SanggahanStatus::DIKABULKAN->value))
                        ->whereHas('pendaftaran.periode', fn (Builder $periode) => $periode
                            ->where('status', 'aktif'));
                });
        });
    }

    /**
     * Gerbang verifikasi dinas: tanggal pendaftaran periode masih berjalan,
     * atau application dibuka kembali lewat sanggahan yang dikabulkan selama
     * periodenya masih aktif.
     */
    public function isVerificationWindowOpen(): bool
    {
        $this->loadMissing(['pendaftaran.periode', 'sanggahan']);

        if ($this->pendaftaran_id) {
            $periode = $this->pendaftaran?->periode;

            if (! $periode || $periode->status !== 'aktif') {
                return false;
            }

            $today = today();

            $isRegistrationOpen = $periode->tanggal_mulai?->lte($today)
                && $periode->tanggal_selesai?->gte($today);

            return $isRegistrationOpen || $this->isReopenedForVerification();
        }

        if ($this->periode === config('kartu_hebat.current_period')) {
            return true;
        }

        return $this->isReopenedForVerification();
    }

    /**
     * Periode yang menjadi konteks application. Application lama hanya
     * menyimpan nama periode sebagai string, sehingga dicocokkan lewat nama.
     */
    public function resolvedPeriode(): ?Periode
    {
        if ($this->pendaftaran_id) {
            $this->loadMissing('pendaftaran.periode');

            return $this->pendaftaran?->periode;
        }

        return Periode::query()
            ->where('nama', $this->periode)
            ->latest('id')
            ->first();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::SUPERADMIN => $query->whereRaw('1 = 0'),
            UserRole::MAHASISWA => $query->where('mahasiswa_id', $user->id),
            UserRole::OPERATOR_DESA => $query->whereHas(
                'mahasiswa.profile',
                fn (Builder $profile) => $profile->where('village_id', $user->village_id),
            ),
            UserRole::OPERATOR_KECAMATAN => $query->whereHas(
                'mahasiswa.profile.village',
                fn (Builder $village) => $village->where('kecamatan_id', $user->kecamatan_id),
            ),
            default => $query->whereHas(
                'mahasiswa.profile.village',
                fn (Builder $village) => $village->where('kabupaten_id', $user->kabupaten_id),
            ),
        };
    }

    public function canBeEditedByStudent(): bool
    {
        return $this->status->isEditableByStudent();
    }

    public function documentCompletionPercentage(): int
    {
        if (! $this->application_type) {
            return 0;
        }

        $requiredTypes = DocumentType::query()
            ->where('is_active', true)
            ->where('is_required', true)
            ->where(function (Builder $query): void {
                $query->whereNull('application_type');

                if ($this->application_type) {
                    $query->orWhere('application_type', $this->application_type->value);
                }
            })
            ->pluck('id');

        if ($requiredTypes->isEmpty()) {
            return 100;
        }

        $uploaded = $this->documents()
            ->whereIn('document_type_id', $requiredTypes)
            ->count();

        return min(100, (int) round(($uploaded / $requiredTypes->count()) * 100));
    }
}
