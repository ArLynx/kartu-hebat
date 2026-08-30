<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
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
