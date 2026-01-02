<?php

namespace Tests\Feature;

use App\Jobs\SendPasswordResetEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendPasswordResetEmailJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_instantiated_with_required_parameters()
    {
        $job = new SendPasswordResetEmail(
            'test@example.com',
            'test-token-123',
            'John Doe'
        );

        $this->assertInstanceOf(SendPasswordResetEmail::class, $job);
    }

    /** @test */
    public function it_handles_password_reset_email_job_successfully()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Password reset link for test@example.com: http://localhost:3000/reset-password?token=test-token-123&email=test%40example.com');

        $job = new SendPasswordResetEmail(
            'test@example.com',
            'test-token-123',
            'John Doe'
        );

        $job->handle();
    }

    /** @test */
    public function it_logs_error_when_email_sending_fails()
    {
        Log::shouldReceive('info')
            ->once()
            ->andThrow(new \Exception('Mail service unavailable'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to send password reset email to test@example.com: Mail service unavailable');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mail service unavailable');

        $job = new SendPasswordResetEmail(
            'test@example.com',
            'test-token-123',
            'John Doe'
        );

        $job->handle();
    }

    /** @test */
    public function it_generates_correct_reset_url()
    {
        $job = new SendPasswordResetEmail(
            'test@example.com',
            'test-token-123',
            'John Doe'
        );

        // Use reflection to access private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('generateResetUrl');
        $method->setAccessible(true);

        $url = $method->invoke($job, 'test-token-123');

        $expectedUrl = 'http://localhost:3000/reset-password?token=test-token-123&email=test%40example.com';
        $this->assertEquals($expectedUrl, $url);
    }

    /** @test */
    public function it_handles_special_characters_in_email()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Password reset link for user+test@example.com: http://localhost:3000/reset-password?token=test-token-123&email=user%2Btest%40example.com');

        $job = new SendPasswordResetEmail(
            'user+test@example.com',
            'test-token-123',
            'Test User'
        );

        $job->handle();
    }

    /** @test */
    public function it_has_correct_retry_configuration()
    {
        $job = new SendPasswordResetEmail(
            'test@example.com',
            'test-token-123',
            'John Doe'
        );

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([60, 300, 900], $job->backoff);
    }

    /** @test */
    public function it_handles_failed_job_logging()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Password reset email job failed for test@example.com: Test failure');

        $exception = new \Exception('Test failure');
        
        $job = new SendPasswordResetEmail(
            'test@example.com',
            'test-token-123',
            'John Doe'
        );

        $job->failed($exception);
    }
}
