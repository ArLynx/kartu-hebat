<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    public function delete(User $user): void
    {
        if ($user->isOperator() || $user->isSuperadmin()) {
            throw ValidationException::withMessages([
                'password' => 'Akun internal hanya dapat dinonaktifkan oleh pengelola sistem.',
            ]);
        }

        $user->deleteProfilePhoto();
        $user->tokens->each->delete();
        $user->notifications()->delete();
        $user->delete();
    }
}
