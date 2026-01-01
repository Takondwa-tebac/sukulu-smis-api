<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @group Fees - Categories
 *
 * APIs for managing fee categories (tuition, boarding, transport, etc.)
 */
class FeeCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:fees.view')->only(['index', 'show']);
        $this->middleware('permission:fees.manage')->only(['store', 'update', 'destroy']);
    }

    /**
     * List fee categories
     *
     * @authenticated
     * @queryParam is_active boolean Filter by active status. Example: true
     * @queryParam is_recurring boolean Filter by recurring status. Example: true
     * @queryParam per_page integer Items per page. Default: 15. Example: 20
     *
     * @response 200 scenario="Success" {"data": [], "links": {}, "meta": {}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = FeeCategory::query()
            ->when($request->is_active !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->is_recurring !== null, fn ($q) => $q->where('is_recurring', $request->boolean('is_recurring')))
            ->orderBy('name');

        return JsonResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_recurring' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $feeCategory = FeeCategory::create([
            'school_id' => $request->user()->school_id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? strtoupper(substr($validated['name'], 0, 4)),
            'description' => $validated['description'] ?? null,
            'is_recurring' => $validated['is_recurring'] ?? true,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Fee category created successfully.',
            'data' => new JsonResource($feeCategory),
        ], 201);
    }

    public function show(FeeCategory $feeCategory): JsonResource
    {
        return new JsonResource($feeCategory);
    }

    public function update(Request $request, FeeCategory $feeCategory): JsonResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_recurring' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $feeCategory->update($validated);

        return new JsonResource($feeCategory);
    }

    public function destroy(FeeCategory $feeCategory): JsonResponse
    {
        if ($feeCategory->feeStructureItems()->exists()) {
            return response()->json([
                'message' => 'Cannot delete fee category used in fee structures.',
            ], 422);
        }

        $feeCategory->delete();

        return response()->json(['message' => 'Fee category deleted successfully.']);
    }
}
