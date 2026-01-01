<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdmissionPeriodResource;
use App\Models\AdmissionPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * @group Admission Periods
 *
 * APIs for managing admission periods
 */
class AdmissionPeriodController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AdmissionPeriod::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->academic_year_id, fn ($q, $id) => $q->where('academic_year_id', $id))
            ->when($request->boolean('only_open'), fn ($q) => $q->open())
            ->withCount('applications')
            ->with('academicYear')
            ->orderByDesc('start_date');

        return AdmissionPeriodResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['nullable', Rule::in(AdmissionPeriod::getStatuses())],
            'max_applications' => ['nullable', 'integer', 'min:1'],
            'application_fee' => ['nullable', 'numeric', 'min:0'],
            'required_documents' => ['nullable', 'array'],
            'eligible_classes' => ['nullable', 'array'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ]);

        $period = AdmissionPeriod::create($validated);

        return (new AdmissionPeriodResource($period->load('academicYear')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AdmissionPeriod $admissionPeriod): AdmissionPeriodResource
    {
        return new AdmissionPeriodResource(
            $admissionPeriod->loadCount('applications')->load('academicYear')
        );
    }

    public function update(Request $request, AdmissionPeriod $admissionPeriod): AdmissionPeriodResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after:start_date'],
            'status' => ['nullable', Rule::in(AdmissionPeriod::getStatuses())],
            'max_applications' => ['nullable', 'integer', 'min:1'],
            'application_fee' => ['nullable', 'numeric', 'min:0'],
            'required_documents' => ['nullable', 'array'],
            'eligible_classes' => ['nullable', 'array'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ]);

        $admissionPeriod->update($validated);

        return new AdmissionPeriodResource($admissionPeriod);
    }

    public function destroy(AdmissionPeriod $admissionPeriod): JsonResponse
    {
        if ($admissionPeriod->applications()->exists()) {
            return response()->json([
                'message' => 'Cannot delete admission period with existing applications.',
            ], 422);
        }

        $admissionPeriod->delete();

        return response()->json(['message' => 'Admission period deleted successfully.']);
    }

    public function open(AdmissionPeriod $admissionPeriod): JsonResponse
    {
        $admissionPeriod->update(['status' => AdmissionPeriod::STATUS_OPEN]);

        return response()->json([
            'message' => 'Admission period opened successfully.',
            'data' => new AdmissionPeriodResource($admissionPeriod),
        ]);
    }

    public function close(AdmissionPeriod $admissionPeriod): JsonResponse
    {
        $admissionPeriod->update(['status' => AdmissionPeriod::STATUS_CLOSED]);

        return response()->json([
            'message' => 'Admission period closed successfully.',
            'data' => new AdmissionPeriodResource($admissionPeriod),
        ]);
    }
}
