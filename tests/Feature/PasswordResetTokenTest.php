<?php

namespace Tests\Feature;

use App\Models\PasswordResetToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTokenTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_password_reset_token()
    {
        $token = PasswordResetToken::create([
            'email' => 'test@example.com',
            'token' => 'test-token-123',
            'created_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'test@example.com',
            'token' => 'test-token-123',
        ]);

        $this->assertInstanceOf(PasswordResetToken::class, $token);
        $this->assertEquals('test@example.com', $token->email);
        $this->assertEquals('test-token-123', $token->token);
    }

    /** @test */
    public function it_can_check_if_token_is_expired()
    {
        // Create expired token
        $expiredToken = PasswordResetToken::create([
            'email' => 'test@example.com',
            'token' => 'expired-token',
            'created_at' => now()->subHours(2),
            'expires_at' => now()->subHour(),
        ]);

        // Create valid token
        $validToken = PasswordResetToken::create([
            'email' => 'test@example.com',
            'token' => 'valid-token',
            'created_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $this->assertTrue($expiredToken->isExpired());
        $this->assertFalse($validToken->isExpired());
    }

    /** @test */
    public function it_can_check_if_token_is_valid()
    {
        // Create expired token
        $expiredToken = PasswordResetToken::create([
            'email' => 'test@example.com',
            'token' => 'expired-token',
            'created_at' => now()->subHours(2),
            'expires_at' => now()->subHour(),
        ]);

        // Create valid token
        $validToken = PasswordResetToken::create([
            'email' => 'test@example.com',
            'token' => 'valid-token',
            'created_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $this->assertFalse($expiredToken->isValid());
        $this->assertTrue($validToken->isValid());
    }

    /** @test */
    public function it_can_delete_expired_tokens()
    {
        // Create expired tokens
        PasswordResetToken::create([
            'email' => 'expired1@example.com',
            'token' => 'expired-token-1',
            'created_at' => now()->subHours(2),
            'expires_at' => now()->subHour(),
        ]);

        PasswordResetToken::create([
            'email' => 'expired2@example.com',
            'token' => 'expired-token-2',
            'created_at' => now()->subHours(3),
            'expires_at' => now()->subHours(2),
        ]);

        // Create valid token
        PasswordResetToken::create([
            'email' => 'valid@example.com',
            'token' => 'valid-token',
            'created_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $deletedCount = PasswordResetToken::deleteExpired();

        $this->assertEquals(2, $deletedCount);

        // Assert expired tokens are deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'expired1@example.com'
        ]);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'expired2@example.com'
        ]);

        // Assert valid token still exists
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'valid@example.com'
        ]);
    }

    /** @test */
    public function it_can_delete_tokens_for_email()
    {
        // Create multiple tokens for same email
        PasswordResetToken::create([
            'email' => 'test@example.com',
            'token' => 'token-1',
            'created_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        PasswordResetToken::create([
            'email' => 'test@example.com',
            'token' => 'token-2',
            'created_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        // Create token for different email
        PasswordResetToken::create([
            'email' => 'other@example.com',
            'token' => 'other-token',
            'created_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $deletedCount = PasswordResetToken::deleteForEmail('test@example.com');

        $this->assertEquals(2, $deletedCount);

        // Assert tokens for test@example.com are deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'test@example.com'
        ]);

        // Assert token for other email still exists
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'other@example.com'
        ]);
    }
}
