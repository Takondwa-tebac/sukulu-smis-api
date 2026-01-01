<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SchoolClass\StreamResource;
use App\Models\SchoolClass;
use App\Models\Stream;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Academic Structure - Streams
 *
 * APIs for managing class streams (e.g., Form 1A, Form 1B)
 */
class StreamController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:streams.view')->only(['index', 'show', 'byClass']);
        $this->middleware('permission:streams.manage')->only(['store', 'update', 'destroy']);
    }

    /**
     * List all streams
     *
     * Get a paginated list of streams, optionally filtered by class.
     *
     * @authenticated
     * @queryParam class_id uuid Filter by class ID. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @queryParam is_active boolean Filter by active status. Example: true
     * @queryParam per_page integer Number of items per page. Default: 15. Example: 20
     *
     * @response 200 scenario="Success" {
     *   "data": [{"id": "uuid", "name": "Stream A", "capacity": 40, "is_active": true}],
     *   "links": {},
     *   "meta": {}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Stream::query()
            ->when($request->class_id, fn ($q, $id) => $q->where('class_id', $id))
            ->when($request->is_active !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->with('schoolClass')
            ->orderBy('name');

        return StreamResource::collection($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Create a stream
     *
     * Create a new stream for a class.
     *
     * @authenticated
     * @bodyParam class_id uuid required The class this stream belongs to. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @bodyParam name string required The stream name. Example: Stream A
     * @bodyParam capacity integer The maximum capacity. Example: 40
     * @bodyParam is_active boolean Whether the stream is active. Default: true. Example: true
     *
     * @response 201 scenario="Created" {"message": "Stream created successfully.", "data": {}}
     * @response 422 scenario="Validation Error" {"message": "The given data was invalid."}
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'uuid', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $stream = Stream::create([
            'school_id' => $request->user()->school_id,
            'class_id' => $validated['class_id'],
            'name' => $validated['name'],
            'capacity' => $validated['capacity'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Stream created successfully.',
            'data' => new StreamResource($stream->load('schoolClass')),
        ], 201);
    }

    /**
     * Get a stream
     *
     * Get details of a specific stream.
     *
     * @authenticated
     * @urlParam stream uuid required The stream ID. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     *
     * @response 200 scenario="Success" {"data": {"id": "uuid", "name": "Stream A"}}
     * @response 404 scenario="Not Found" {"message": "Stream not found."}
     */
    public function show(Stream $stream): StreamResource
    {
        return new StreamResource($stream->load('schoolClass'));
    }

    /**
     * Update a stream
     *
     * Update an existing stream.
     *
     * @authenticated
     * @urlParam stream uuid required The stream ID. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @bodyParam name string The stream name. Example: Stream B
     * @bodyParam capacity integer The maximum capacity. Example: 45
     * @bodyParam is_active boolean Whether the stream is active. Example: false
     *
     * @response 200 scenario="Success" {"data": {"id": "uuid", "name": "Stream B"}}
     */
    public function update(Request $request, Stream $stream): StreamResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $stream->update($validated);

        return new StreamResource($stream->load('schoolClass'));
    }

    /**
     * Delete a stream
     *
     * Delete a stream. Cannot delete if students are enrolled.
     *
     * @authenticated
     * @urlParam stream uuid required The stream ID. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     *
     * @response 200 scenario="Success" {"message": "Stream deleted successfully."}
     * @response 422 scenario="Has Students" {"message": "Cannot delete stream with enrolled students."}
     */
    public function destroy(Stream $stream): JsonResponse
    {
        if ($stream->enrollments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete stream with enrolled students.',
            ], 422);
        }

        $stream->delete();

        return response()->json(['message' => 'Stream deleted successfully.']);
    }

    /**
     * Get streams by class
     *
     * Get all streams for a specific class.
     *
     * @authenticated
     * @urlParam class uuid required The class ID. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     *
     * @response 200 scenario="Success" {"data": [{"id": "uuid", "name": "Stream A"}]}
     */
    public function byClass(SchoolClass $class): AnonymousResourceCollection
    {
        $streams = $class->streams()->orderBy('name')->get();

        return StreamResource::collection($streams);
    }
}
