<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateInvoicesJob;
use App\Models\FeeStructure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

/**
 * @group Fees - Structures
 *
 * APIs for managing fee structures and generating invoices
 */
class FeeStructureController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:fees.view')->only(['index', 'show']);
        $this->middleware('permission:fees.manage')->only(['store', 'update', 'destroy', 'duplicate']);
        $this->middleware('permission:invoices.generate')->only(['generateInvoices']);
    }

    /**
     * List fee structures
     *
     * @authenticated
     * @queryParam academic_year_id uuid Filter by academic year. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @queryParam term_id uuid Filter by term. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @queryParam class_id uuid Filter by class. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @queryParam is_active boolean Filter by active status. Example: true
     *
     * @response 200 scenario="Success" {"data": [], "links": {}, "meta": {}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = FeeStructure::query()
            ->when($request->academic_year_id, fn ($q, $id) => $q->where('academic_year_id', $id))
            ->when($request->term_id, fn ($q, $id) => $q->where('term_id', $id))
            ->when($request->class_id, fn ($q, $id) => $q->where('class_id', $id))
            ->when($request->is_active !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->with(['academicYear', 'term', 'schoolClass', 'items.feeCategory'])
            ->orderByDesc('created_at');

        return JsonResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'term_id' => ['required', 'uuid', 'exists:terms,id'],
            'class_id' => ['nullable', 'uuid', 'exists:classes,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'due_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.fee_category_id' => ['required', 'uuid', 'exists:fee_categories,id'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $feeStructure = DB::transaction(function () use ($validated, $request) {
            $feeStructure = FeeStructure::create([
                'school_id' => $request->user()->school_id,
                'name' => $validated['name'],
                'academic_year_id' => $validated['academic_year_id'],
                'term_id' => $validated['term_id'],
                'class_id' => $validated['class_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'total_amount' => collect($validated['items'])->sum('amount'),
            ]);

            foreach ($validated['items'] as $item) {
                $feeStructure->items()->create([
                    'fee_category_id' => $item['fee_category_id'],
                    'amount' => $item['amount'],
                    'description' => $item['description'] ?? null,
                ]);
            }

            return $feeStructure;
        });

        return response()->json([
            'message' => 'Fee structure created successfully.',
            'data' => new JsonResource($feeStructure->load(['academicYear', 'term', 'schoolClass', 'items.feeCategory'])),
        ], 201);
    }

    public function show(FeeStructure $feeStructure): JsonResource
    {
        return new JsonResource($feeStructure->load(['academicYear', 'term', 'schoolClass', 'items.feeCategory']));
    }

    public function update(Request $request, FeeStructure $feeStructure): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'due_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.fee_category_id' => ['required_with:items', 'uuid', 'exists:fee_categories,id'],
            'items.*.amount' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated, $feeStructure) {
            $feeStructure->update([
                'name' => $validated['name'] ?? $feeStructure->name,
                'description' => $validated['description'] ?? $feeStructure->description,
                'due_date' => $validated['due_date'] ?? $feeStructure->due_date,
                'is_active' => $validated['is_active'] ?? $feeStructure->is_active,
            ]);

            if (isset($validated['items'])) {
                $feeStructure->items()->delete();
                foreach ($validated['items'] as $item) {
                    $feeStructure->items()->create([
                        'fee_category_id' => $item['fee_category_id'],
                        'amount' => $item['amount'],
                        'description' => $item['description'] ?? null,
                    ]);
                }
                $feeStructure->update([
                    'total_amount' => collect($validated['items'])->sum('amount'),
                ]);
            }
        });

        return response()->json([
            'message' => 'Fee structure updated successfully.',
            'data' => new JsonResource($feeStructure->fresh()->load(['items.feeCategory'])),
        ]);
    }

    public function destroy(FeeStructure $feeStructure): JsonResponse
    {
        if ($feeStructure->invoices()->exists()) {
            return response()->json([
                'message' => 'Cannot delete fee structure with generated invoices.',
            ], 422);
        }

        $feeStructure->items()->delete();
        $feeStructure->delete();

        return response()->json(['message' => 'Fee structure deleted successfully.']);
    }

    public function generateInvoices(Request $request, FeeStructure $feeStructure): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['nullable', 'uuid', 'exists:classes,id'],
        ]);

        GenerateInvoicesJob::dispatch(
            $request->user()->school_id,
            $feeStructure->academic_year_id,
            $feeStructure->term_id,
            $validated['class_id'] ?? $feeStructure->class_id,
            $feeStructure->id
        );

        return response()->json([
            'message' => 'Invoice generation job has been queued.',
        ]);
    }

    public function duplicate(Request $request, FeeStructure $feeStructure): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'term_id' => ['required', 'uuid', 'exists:terms,id'],
        ]);

        $newStructure = DB::transaction(function () use ($validated, $feeStructure, $request) {
            $newStructure = $feeStructure->replicate();
            $newStructure->name = $validated['name'];
            $newStructure->academic_year_id = $validated['academic_year_id'];
            $newStructure->term_id = $validated['term_id'];
            $newStructure->save();

            foreach ($feeStructure->items as $item) {
                $newItem = $item->replicate();
                $newItem->fee_structure_id = $newStructure->id;
                $newItem->save();
            }

            return $newStructure;
        });

        return response()->json([
            'message' => 'Fee structure duplicated successfully.',
            'data' => new JsonResource($newStructure->load(['items.feeCategory'])),
        ], 201);
    }
}
