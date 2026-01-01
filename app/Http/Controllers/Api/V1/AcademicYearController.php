<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYear\StoreAcademicYearRequest;
use App\Http\Requests\AcademicYear\UpdateAcademicYearRequest;
use App\Http\Resources\AcademicYear\AcademicYearResource;
use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Academic Years
 *
 * APIs for managing academic years
 */
class AcademicYearController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AcademicYear::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->boolean('include_terms'), fn ($q) => $q->with('terms'))
            ->orderByDesc('start_date');

        return AcademicYearResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $academicYear = AcademicYear::create($request->validated());

        return (new AcademicYearResource($academicYear))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AcademicYear $academicYear): AcademicYearResource
    {
        return new AcademicYearResource($academicYear->load('terms'));
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): AcademicYearResource
    {
        $academicYear->update($request->validated());

        return new AcademicYearResource($academicYear);
    }

    public function destroy(AcademicYear $academicYear): JsonResponse
    {
        $academicYear->delete();

        return response()->json(['message' => 'Academic year deleted successfully.']);
    }

    public function setCurrent(AcademicYear $academicYear): JsonResponse
    {
        $academicYear->setAsCurrent();

        return response()->json([
            'message' => 'Academic year set as current.',
            'data' => new AcademicYearResource($academicYear),
        ]);
    }

    public function current(): JsonResponse
    {
        $academicYear = AcademicYear::current()->with('terms')->first();

        if (!$academicYear) {
            return response()->json(['message' => 'No current academic year set.'], 404);
        }

        return response()->json(['data' => new AcademicYearResource($academicYear)]);
    }
}
