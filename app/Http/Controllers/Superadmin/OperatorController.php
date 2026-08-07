<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\User;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class OperatorController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->whereIn('role', UserRole::operatorValues())
            ->with(['village.kecamatan', 'kecamatan', 'kabupaten']);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        $operators = $query
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.operators.index', [
            'operators' => $operators,
            'roles' => $this->operatorRoleOptions(),
            'filters' => [
                'q' => $search,
                'role' => $role,
            ],
        ]);
    }

    public function create(): View
    {
        return view('superadmin.operators.create', [
            'roles' => $this->operatorRoleOptions(),
            'kabupatens' => Kabupaten::query()->orderBy('name')->get(),
            'kecamatans' => Kecamatan::query()->orderBy('name')->get(),
            'villages' => Village::query()->orderBy('name')->limit(200)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateOperator($request);

        $created = null;
        $generatedPassword = $data['password'];

        DB::transaction(function () use ($data, &$created): void {
            $created = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'status' => $data['status'] ?? 'active',
                'village_id' => $data['village_id'] ?? null,
                'kecamatan_id' => $data['kecamatan_id'] ?? null,
                'kabupaten_id' => $data['kabupaten_id'] ?? null,
            ]);

            $created->forceFill(['email_verified_at' => now()])->save();
        });

        $request->session()->flash(
            'generated_password',
            $created->email.' · password: '.$generatedPassword,
        );

        return redirect()
            ->route('superadmin.operators.edit', $created)
            ->with('success', 'Operator berhasil dibuat. Catat password di bawah ini dan segera kirim ke operator.');
    }

    public function edit(User $operator): View
    {
        abort_unless($this->isOperatorRole($operator->role), 404);

        return view('superadmin.operators.edit', [
            'operator' => $operator,
            'roles' => $this->operatorRoleOptions(),
            'kabupatens' => Kabupaten::query()->orderBy('name')->get(),
            'kecamatans' => Kecamatan::query()->orderBy('name')->get(),
            'villages' => Village::query()->orderBy('name')->limit(200)->get(),
        ]);
    }

    public function update(Request $request, User $operator): RedirectResponse
    {
        abort_unless($this->isOperatorRole($operator->role), 404);

        $data = $this->validateOperator($request, $operator);

        DB::transaction(function () use ($operator, $data): void {
            $operator->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'status' => $data['status'] ?? 'active',
                'village_id' => $data['village_id'] ?? null,
                'kecamatan_id' => $data['kecamatan_id'] ?? null,
                'kabupaten_id' => $data['kabupaten_id'] ?? null,
            ]);

            if (! empty($data['password'])) {
                $operator->forceFill([
                    'password' => Hash::make($data['password']),
                ])->save();
            }
        });

        return redirect()
            ->route('superadmin.operators.edit', $operator)
            ->with('success', 'Operator berhasil diperbarui.');
    }

    public function destroy(User $operator): RedirectResponse
    {
        abort_unless($this->isOperatorRole($operator->role), 404);

        $hasHistory = \App\Models\VillageVerification::where('verifier_id', $operator->id)->exists()
            || \App\Models\DistrictVerification::where('verifier_id', $operator->id)->exists()
            || \App\Models\AgencyVerification::where('verifier_id', $operator->id)->exists();

        if ($hasHistory) {
            return back()->with(
                'error',
                'Operator tidak dapat dihapus karena sudah pernah menandatangani verifikasi. Nonaktifkan akun sebagai gantinya.',
            );
        }

        $operator->delete();

        return redirect()
            ->route('superadmin.operators.index')
            ->with('success', 'Operator berhasil dihapus.');
    }

    public function resetPassword(Request $request, User $operator): RedirectResponse
    {
        abort_unless($this->isOperatorRole($operator->role), 404);

        $newPassword = Str::random(12);

        $operator->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        $request->session()->flash(
            'generated_password',
            $operator->email.' · password baru: '.$newPassword,
        );

        return redirect()
            ->route('superadmin.operators.edit', $operator)
            ->with('success', 'Password operator berhasil di-reset.');
    }

    private function validateOperator(Request $request, ?User $operator = null): array
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => strtolower(trim((string) $request->input('email'))),
            'status' => $request->input('status', 'active'),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($operator),
            ],
            'role' => [
                'required',
                Rule::in(UserRole::operatorValues()),
            ],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'password' => [
                'nullable',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'village_id' => ['nullable', 'integer', Rule::exists('villages', 'id')],
            'kecamatan_id' => ['nullable', 'integer', Rule::exists('kecamatans', 'id')],
            'kabupaten_id' => ['nullable', 'integer', Rule::exists('kabupatens', 'id')],
        ]);

        if (! $operator && empty($validated['password'])) {
            $validated['password'] = Str::random(12);
        } elseif (empty($validated['password'])) {
            unset($validated['password']);
        }

        $role = $validated['role'];
        $defaults = $this->regionDefaults($role);

        if (! empty($validated['village_id']) && $role === UserRole::OPERATOR_DESA->value) {
            $validated['kecamatan_id'] = Village::query()
                ->whereKey($validated['village_id'])
                ->value('kecamatan_id');
        }

        if (empty($validated['kecamatan_id'])) {
            $validated['kecamatan_id'] = $defaults['kecamatan_id'];
        }
        if (empty($validated['kabupaten_id'])) {
            $validated['kabupaten_id'] = $defaults['kabupaten_id'];
        }

        if ($role !== UserRole::OPERATOR_DESA->value) {
            $validated['village_id'] = null;
        }

        return $validated;
    }

    private function regionDefaults(string $role): array
    {
        $kabupaten = Kabupaten::query()->first();

        return match ($role) {
            UserRole::OPERATOR_DESA->value => [
                'kecamatan_id' => $kabupaten?->id ? Kecamatan::query()->first()?->id : null,
                'kabupaten_id' => $kabupaten?->id,
            ],
            UserRole::OPERATOR_KECAMATAN->value => [
                'kecamatan_id' => Kecamatan::query()->first()?->id,
                'kabupaten_id' => $kabupaten?->id,
            ],
            default => [
                'kecamatan_id' => null,
                'kabupaten_id' => $kabupaten?->id,
            ],
        };
    }

    private function operatorRoleOptions(): array
    {
        return collect(UserRole::cases())
            ->filter(fn (UserRole $role) => in_array($role->value, UserRole::operatorValues(), true))
            ->mapWithKeys(fn (UserRole $role) => [$role->value => $role->label()])
            ->all();
    }

    private function isOperatorRole(UserRole $role): bool
    {
        return in_array($role->value, UserRole::operatorValues(), true);
    }
}