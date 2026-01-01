<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\SendNotificationRequest;
use App\Http\Requests\Notification\StoreNotificationTemplateRequest;
use App\Http\Resources\Notification\NotificationResource;
use App\Http\Resources\Notification\NotificationTemplateResource;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Notifications
 *
 * APIs for managing notifications
 */
class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    // Templates
    public function templates(Request $request): AnonymousResourceCollection
    {
        $query = NotificationTemplate::query()
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name');

        return NotificationTemplateResource::collection($query->paginate($request->input('per_page', 50)));
    }

    public function storeTemplate(StoreNotificationTemplateRequest $request): JsonResponse
    {
        $template = NotificationTemplate::create([
            'school_id' => $request->user()->school_id,
            ...$request->validated(),
        ]);

        return (new NotificationTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    public function showTemplate(NotificationTemplate $notificationTemplate): NotificationTemplateResource
    {
        return new NotificationTemplateResource($notificationTemplate);
    }

    public function updateTemplate(StoreNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate): NotificationTemplateResource
    {
        if ($notificationTemplate->is_system) {
            abort(422, 'System templates cannot be modified.');
        }

        $notificationTemplate->update($request->validated());

        return new NotificationTemplateResource($notificationTemplate);
    }

    public function destroyTemplate(NotificationTemplate $notificationTemplate): JsonResponse
    {
        if ($notificationTemplate->is_system) {
            return response()->json(['message' => 'System templates cannot be deleted.'], 422);
        }

        $notificationTemplate->delete();

        return response()->json(['message' => 'Template deleted successfully.']);
    }

    // Notifications
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Notification::query()
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->with(['template', 'createdByUser'])
            ->withCount('recipients')
            ->orderByDesc('created_at');

        return NotificationResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function show(Notification $notification): NotificationResource
    {
        return new NotificationResource(
            $notification->load(['template', 'createdByUser', 'recipients'])
        );
    }

    public function send(SendNotificationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $notification = $this->notificationService->send([
            'school_id' => $request->user()->school_id,
            'template_id' => $validated['template_id'] ?? null,
            'type' => $validated['type'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'body' => $validated['body'] ?? null,
            'data' => $validated['data'] ?? [],
            'recipients' => $validated['recipients'],
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Notification sent successfully.',
            'data' => new NotificationResource($notification->load('recipients')),
        ]);
    }

    public function userNotifications(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $notifications = NotificationRecipient::where('recipient_type', 'App\\Models\\User')
            ->where('recipient_id', $userId)
            ->with('notification')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return NotificationResource::collection(
            $notifications->through(fn ($r) => $r->notification)
        );
    }

    public function markAsRead(NotificationRecipient $notificationRecipient): JsonResponse
    {
        $notificationRecipient->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        NotificationRecipient::where('recipient_type', 'App\\Models\\User')
            ->where('recipient_id', $request->user()->id)
            ->where('status', '!=', NotificationRecipient::STATUS_READ)
            ->update([
                'status' => NotificationRecipient::STATUS_READ,
                'read_at' => now(),
            ]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = NotificationRecipient::where('recipient_type', 'App\\Models\\User')
            ->where('recipient_id', $request->user()->id)
            ->where('status', '!=', NotificationRecipient::STATUS_READ)
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}
