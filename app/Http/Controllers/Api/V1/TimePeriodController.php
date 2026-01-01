<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timetable\StoreTimePeriodRequest;
use App\Http\Resources\Timetable\TimePeriodResource;
use App\Models\TimePeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Time Periods
 *
 * APIs for managing school time periods
 */
class TimePeriodController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TimePeriod::query()
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->ordered();

        return TimePeriodResource::collection($query->paginate($request->input('per_page', 50)));
    }

    public function store(StoreTimePeriodRequest $request): JsonResponse
    {
        $period = TimePeriod::create([
            'school_id' => $request->user()->school_id,
            ...$request->validated(),
        ]);

        return (new TimePeriodResource($period))
            ->response()
            ->setStatusCode(201);
    }

    public function show(TimePeriod $timePeriod): TimePeriodResource
    {
        return new TimePeriodResource($timePeriod);
    }

    public function update(StoreTimePeriodRequest $request, TimePeriod $timePeriod): TimePeriodResource
    {
        $timePeriod->update($request->validated());

        return new TimePeriodResource($timePeriod);
    }

    public function destroy(TimePeriod $timePeriod): JsonResponse
    {
        if ($timePeriod->timetableSlots()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a time period that is used in timetables.',
            ], 422);
        }

        $timePeriod->delete();

        return response()->json(['message' => 'Time period deleted successfully.']);
    }
}
