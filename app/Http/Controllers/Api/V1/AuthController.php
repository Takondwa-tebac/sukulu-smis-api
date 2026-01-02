<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\PasswordResetToken;
use App\Jobs\SendPasswordResetEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * APIs for user authentication
 */
class AuthController extends Controller
{
    /**
     * Login
     *
     * Authenticate a user and return an access token.
     *
     * @bodyParam email string required User's email address. Example: admin@sukulu.com
     * @bodyParam password string required User's password. Example: password
     * @unauthenticated
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact support.'],
            ]);
        }

        if ($user->school_id && $user->school) {
            if (!$user->school->isActive()) {
                throw ValidationException::withMessages([
                    'email' => ['Your school account has been suspended. Please contact support.'],
                ]);
            }
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth-token', ['*'], now()->addDays(7));

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user->load('roles', 'permissions')),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toISOString(),
        ]);
    }

    /**
     * Logout
     *
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Logout from all devices
     *
     * Revoke all access tokens for the authenticated user.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices successfully',
        ]);
    }

    /**
     * Get authenticated user
     *
     * Get the currently authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles', 'permissions', 'school']);

        return response()->json([
            'user' => new UserResource($user),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'roles' => $user->roles->pluck('name'),
            'school' => $user->school ? [
                'id' => $user->school->id,
                'name' => $user->school->name,
                'code' => $user->school->code,
                'type' => $user->school->type,
                'enabled_modules' => $user->school->enabled_modules,
            ] : null,
        ]);
    }

    /**
     * Refresh token
     *
     * Generate a new access token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->user()->currentAccessToken()->delete();
        
        $token = $user->createToken('auth-token', ['*'], now()->addDays(7));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toISOString(),
        ]);
    }

    /**
     * Change password
     *
     * Change the authenticated user's password.
     *
     * @bodyParam current_password string required Current password
     * @bodyParam password string required New password (min 8 characters)
     * @bodyParam password_confirmation string required Password confirmation
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $user->tokens()->delete();
        
        $token = $user->createToken('auth-token', ['*'], now()->addDays(7));

        return response()->json([
            'message' => 'Password changed successfully. All other sessions have been logged out.',
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Forgot password
     *
     * Send password reset link to user's email.
     *
     * @bodyParam email string required User's email address. Example: user@example.com
     *
     * @response 200 scenario="Success" {"message": "Password reset link sent to your email"}
     * @response 404 scenario="Not Found" {"error": "No account found with this email"}
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            // Don't reveal if email exists or not for security
            return response()->json([
                'message' => 'If an account exists with this email, a password reset link has been sent.',
            ]);
        }

        // Delete any existing tokens for this email
        PasswordResetToken::deleteForEmail($validated['email']);
        PasswordResetToken::deleteExpired();

        // Generate a secure random token
        $token = Str::random(60);
        
        // Create password reset token
        PasswordResetToken::create([
            'email' => $validated['email'],
            'token' => $token,
            'created_at' => now(),
            'expires_at' => now()->addHours(1), // Token expires in 1 hour
        ]);

        // Send password reset email as background job
        SendPasswordResetEmail::dispatch(
            $validated['email'],
            $token,
            $user->full_name
        );
        
        return response()->json([
            'message' => 'If an account exists with this email, a password reset link has been sent.',
            'status' => 'success'
        ]);
    }

    /**
     * Reset password
     *
     * Reset user password using valid token.
     *
     * @bodyParam email string required User's email address. Example: user@example.com
     * @bodyParam token string required Password reset token. Example: abc123...
     * @bodyParam password string required New password (min 8 characters). Example: newpassword
     * @bodyParam password_confirmation string required Password confirmation. Example: newpassword
     *
     * @response 200 scenario="Success" {"message": "Password reset successfully"}
     * @response 400 scenario="Invalid Token" {"error": "Invalid or expired reset token"}
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Find the reset token
        $resetToken = PasswordResetToken::where('email', $validated['email'])
            ->where('token', $validated['token'])
            ->first();

        if (!$resetToken || !$resetToken->isValid()) {
            return response()->json([
                'error' => 'Invalid or expired reset token'
            ], 400);
        }

        // Find the user
        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        // Reset the password
        $user->password = Hash::make($validated['password']);
        $user->save();

        // Delete the reset token
        $resetToken->delete();

        // Logout all other sessions for security
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successfully. Please login with your new password.'
        ]);
    }
}
