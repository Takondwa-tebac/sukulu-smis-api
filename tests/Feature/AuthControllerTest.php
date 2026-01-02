<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PasswordResetToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Jobs\SendPasswordResetEmail;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    /** @test */
    public function it_can_request_password_reset_with_valid_email()
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'If an account exists with this email, a password reset link has been sent.',
                    'status' => 'success'
                ]);

        // Assert token was created
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'test@example.com'
        ]);

        // Assert email job was dispatched
        Queue::assertPushed(SendPasswordResetEmail::class, function ($job) {
            return $job->email === 'test@example.com';
        });
    }

    /** @test */
    public function it_handles_password_reset_request_for_nonexistent_email()
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'If an account exists with this email, a password reset link has been sent.',
                ]);

        // Assert no token was created
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'nonexistent@example.com'
        ]);

        // Assert no email job was dispatched
        Queue::assertNotPushed(SendPasswordResetEmail::class);
    }

    /** @test */
    public function it_validates_email_for_password_reset_request()
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'invalid-email'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_requires_email_for_password_reset_request()
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_can_reset_password_with_valid_token()
    {
        // Create a reset token
        $token = PasswordResetToken::create([
            'email' => 'test@example.com',
            'token' => 'valid-token-123',
            'created_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => 'valid-token-123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Password reset successfully. Please login with your new password.'
                ]);

        // Assert password was updated
        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));

        // Assert token was deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'test@example.com',
            'token' => 'valid-token-123',
        ]);
    }

    /** @test */
    public function it_rejects_password_reset_with_invalid_token()
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Invalid or expired reset token'
                ]);
    }

    /** @test */
    public function it_rejects_password_reset_with_expired_token()
    {
        // Create an expired token
        PasswordResetToken::create([
            'email' => 'test@example.com',
            'token' => 'expired-token-123',
            'created_at' => now()->subHours(2),
            'expires_at' => now()->subHour(), // Expired 1 hour ago
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => 'expired-token-123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Invalid or expired reset token'
                ]);
    }

    /** @test */
    public function it_validates_password_reset_fields()
    {
        $response = $this->postJson('/api/v1/auth/reset-password', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'token', 'password', 'password_confirmation']);
    }

    /** @test */
    public function it_validates_password_minimum_length()
    {
        PasswordResetToken::create([
            'email' => 'test@example.com',
            'token' => 'valid-token-123',
            'created_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => 'valid-token-123',
            'password' => '123', // Too short
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_validates_password_confirmation()
    {
        PasswordResetToken::create([
            'email' => 'test@example.com',
            'token' => 'valid-token-123',
            'created_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => 'valid-token-123',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }
}
