<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_screen_cannot_be_rendered_if_support_is_disabled(): void
    {
        if (Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_new_users_can_register(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_user_with_stale_session_hash_lands_on_verify_email_not_login(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->withSession([
            'password_hash_web' => 'stale-hash-from-deleted-user',
            'password_hash_sanctum' => 'stale-hash-from-deleted-user',
        ])
            ->post('/register', [
                'name' => 'Elsha Test',
                'email' => 'elsha@gmail.com',
                'password' => 'Password@123',
                'password_confirmation' => 'Password@123',
                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        // Saat mengikuti redirect ke /dashboard, harus diarahkan ke /email/verify (bukan /login)
        $follow = $this->get(route('dashboard'));
        $follow->assertRedirect(route('verification.notice'));

        // Halaman /email/verify harus berhasil dirender (status 200)
        $verifyScreen = $this->get(route('verification.notice'));
        $verifyScreen->assertOk();
    }

    public function test_registration_rolls_back_and_returns_to_form_when_email_sending_fails(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        Notification::shouldReceive('send')
            ->andThrow(new TransportException('Connection timed out'));

        $response = $this->from('/register')->post('/register', [
            'name' => 'Offline Student',
            'email' => 'offline@gmail.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        // Akun harus dibatalkan dari database (rollback)
        $this->assertDatabaseMissing('users', ['email' => 'offline@gmail.com']);
        $this->assertGuest();

        // Dikembalikan ke halaman register dengan error dan input lama tetap terisi
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email']);
        $this->assertSame('Offline Student', session('_old_input.name'));
        $this->assertSame('offline@gmail.com', session('_old_input.email'));
    }

    public function test_registration_validates_minimum_password_length(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->from('/register')->post('/register', [
            'name' => 'Short Pw Student',
            'email' => 'shortpw@gmail.com',
            'password' => 'Short@1',
            'password_confirmation' => 'Short@1',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', ['email' => 'shortpw@gmail.com']);
        $this->assertGuest();
    }

    public function test_registration_validates_password_requires_uppercase_and_symbol(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        // Tanpa huruf kapital (hanya huruf kecil + simbol)
        $responseNoUpper = $this->from('/register')->post('/register', [
            'name' => 'No Uppercase Student',
            'email' => 'noupper@gmail.com',
            'password' => 'password@123',
            'password_confirmation' => 'password@123',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $responseNoUpper->assertRedirect('/register');
        $responseNoUpper->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', ['email' => 'noupper@gmail.com']);

        // Tanpa simbol (hanya huruf kapital + huruf kecil + angka)
        $responseNoSymbol = $this->from('/register')->post('/register', [
            'name' => 'No Symbol Student',
            'email' => 'nosymbol@gmail.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $responseNoSymbol->assertRedirect('/register');
        $responseNoSymbol->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', ['email' => 'nosymbol@gmail.com']);
    }

    public function test_registration_validates_password_confirmation_match(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->from('/register')->post('/register', [
            'name' => 'Mismatch Student',
            'email' => 'mismatch@gmail.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Different@123',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', ['email' => 'mismatch@gmail.com']);
        $this->assertGuest();
    }

    public function test_registration_rejects_disposable_email_domain(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->from('/register')->post('/register', [
            'name' => 'Temp Mail Student',
            'email' => 'student@mailinator.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('users', ['email' => 'student@mailinator.com']);
        $this->assertGuest();
    }

    public function test_registration_rejects_unknown_email_domain(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->from('/register')->post('/register', [
            'name' => 'Unknown Domain Student',
            'email' => 'student@alamat-ngawur.xyz',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('users', ['email' => 'student@alamat-ngawur.xyz']);
        $this->assertGuest();
    }

    public function test_registration_allows_institutional_email_domain(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'Campus Student',
            'email' => 'campus@student.univ.ac.id',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'campus@student.univ.ac.id']);
    }

    public function test_registration_allows_trusted_provider_email_in_uppercase(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'Uppercase Student',
            'email' => 'Uppercase@GMAIL.COM',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'uppercase@gmail.com']);
    }
}
