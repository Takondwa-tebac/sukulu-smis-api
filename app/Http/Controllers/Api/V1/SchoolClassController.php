<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolClass\StoreSchoolClassRequest;
use App\Http\Requests\SchoolClass\UpdateSchoolClassRequest;
use App\Http\Resources\SchoolClass\SchoolClassResource;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Classes
 *
 * APIs for managing school classes/grades
 */
class SchoolClassController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = SchoolClass::query()
            ->when($request->level, fn ($q, $level) => $q->where('level', $level))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->boolean('include_streams'), fn ($q) => $q->with('streams'))
            ->ordered();

        return SchoolClassResource::collection($query->paginate($request->input('per_page', 50)));
    }

    public function store(StoreSchoolClassRequest $request): JsonResponse
    {
        $class = SchoolClass::create($request->validated());

        return (new SchoolClassResource($class))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SchoolClass $class): SchoolClassResource
    {
        return new SchoolClassResource($class->load('streams'));
    }

    public function update(UpdateSchoolClassRequest $request, SchoolClass $class): SchoolClassResource
    {
        $class->update($request->validated());

        return new SchoolClassResource($class);
    }

    public function destroy(SchoolClass $class): JsonResponse
    {
        $class->delete();

        return response()->json(['message' => 'Class deleted successfully.']);
    }
}
