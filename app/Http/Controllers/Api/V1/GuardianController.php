<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guardian\StoreGuardianRequest;
use App\Http\Requests\Guardian\UpdateGuardianRequest;
use App\Http\Resources\Student\GuardianResource;
use App\Models\Guardian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
        $guardian = Guardian::create($request->validated());

        return (new GuardianResource($guardian))
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
