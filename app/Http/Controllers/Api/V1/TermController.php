<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Term\StoreTermRequest;
use App\Http\Requests\Term\UpdateTermRequest;
use App\Http\Resources\AcademicYear\TermResource;
use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Terms
 *
 * APIs for managing academic terms/semesters
 */
class TermController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Term::query()
            ->when($request->academic_year_id, fn ($q, $id) => $q->where('academic_year_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->with('academicYear')
            ->orderBy('term_number');

        return TermResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(StoreTermRequest $request): JsonResponse
    {
        $term = Term::create($request->validated());

        return (new TermResource($term->load('academicYear')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Term $term): TermResource
    {
        return new TermResource($term->load('academicYear'));
    }

    public function update(UpdateTermRequest $request, Term $term): TermResource
    {
        $term->update($request->validated());

        return new TermResource($term);
    }

    public function destroy(Term $term): JsonResponse
    {
        $term->delete();

        return response()->json(['message' => 'Term deleted successfully.']);
    }

    public function setCurrent(Term $term): JsonResponse
    {
        $term->setAsCurrent();

        return response()->json([
            'message' => 'Term set as current.',
            'data' => new TermResource($term),
        ]);
    }

    public function current(): JsonResponse
    {
        $term = Term::current()->with('academicYear')->first();

        if (!$term) {
            return response()->json(['message' => 'No current term set.'], 404);
        }

        return response()->json(['data' => new TermResource($term)]);
    }
}
