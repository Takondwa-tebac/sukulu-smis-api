<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guardian\StoreGuardianRequest;
use App\Http\Requests\Guardian\UpdateGuardianRequest;
use App\Http\Resources\Student\GuardianResource;
use App\Jobs\SendUserWelcomeEmailJob;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @group Guardians
 *
 * APIs for managing guardians/parents
 */
class GuardianController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Guardian::query()
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone_primary', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->boolean('include_students'), fn ($q) => $q->with('students'))
            ->orderBy('first_name')
            ->orderBy('last_name');

        return GuardianResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(StoreGuardianRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $createUserAccount = $validated['create_user_account'] ?? false;
        $sendWelcomeEmail = $validated['send_welcome_email'] ?? true;
        
        // Remove non-guardian fields before creating
        unset($validated['create_user_account'], $validated['send_welcome_email']);
        
        $guardian = DB::transaction(function () use ($validated, $createUserAccount, $sendWelcomeEmail) {
            $guardian = Guardian::create($validated);
            
            // Optionally create a user account for portal access
            if ($createUserAccount && !empty($validated['email'])) {
                $temporaryPassword = Str::random(12);
                
                $user = User::create([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'phone_number' => $validated['phone_primary'] ?? null,
                    'password' => Hash::make($temporaryPassword),
                    'school_id' => $guardian->school_id,
                    'status' => 'active',
                ]);
                
                // Assign parent role
                $user->assignRole('parent');
                
                // Link user to guardian
                $guardian->update(['user_id' => $user->id]);
                
                // Send welcome email with credentials
                if ($sendWelcomeEmail) {
                    SendUserWelcomeEmailJob::dispatch($user->id, $temporaryPassword);
                }
            }
            
            return $guardian;
        });

        return (new GuardianResource($guardian->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Guardian $guardian): GuardianResource
    {
        return new GuardianResource($guardian->load('students'));
    }

    public function update(UpdateGuardianRequest $request, Guardian $guardian): GuardianResource
    {
        $guardian->update($request->validated());

        return new GuardianResource($guardian);
    }

    public function destroy(Guardian $guardian): JsonResponse
    {
        $guardian->delete();

        return response()->json(['message' => 'Guardian deleted successfully.']);
    }
}
