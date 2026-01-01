<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group File Uploads
 *
 * APIs for uploading files (photos, documents) using Spatie Media Library
 */
class FileUploadController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:students.update')->only(['uploadStudentPhoto', 'uploadStudentDocument', 'getStudentDocuments', 'deleteStudentDocument']);
        $this->middleware('permission:settings.manage')->only(['uploadSchoolLogo', 'uploadSchoolBanner', 'uploadSchoolDocument']);
    }

    /**
     * Upload student photo
     *
     * Upload a profile photo for a student.
     *
     * @authenticated
     * @urlParam student uuid required The student ID. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @bodyParam photo file required Image file (max 2MB). No-example
     *
     * @response 200 scenario="Success" {"message": "Student photo uploaded successfully.", "url": "https://..."}
     * @response 422 scenario="Validation Error" {"message": "The photo must be an image."}
     */
    public function uploadStudentPhoto(Request $request, Student $student): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        $student->addMediaFromRequest('photo')
            ->toMediaCollection('photo');

        return response()->json([
            'message' => 'Student photo uploaded successfully.',
            'url' => $student->getFirstMediaUrl('photo'),
        ]);
    }

    public function uploadStudentDocument(Request $request, Student $student): JsonResponse
    {
        $request->validate([
            'document' => ['required', 'file', 'max:5120', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'document_type' => ['required', 'string', 'in:birth_certificate,transfer_letter,medical_record,other'],
        ]);

        $documentType = $request->input('document_type');
        $collection = $documentType === 'birth_certificate' ? 'birth_certificate' : 'documents';

        $media = $student->addMediaFromRequest('document')
            ->withCustomProperties(['type' => $documentType])
            ->toMediaCollection($collection);

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'media_id' => $media->id,
            'url' => $media->getUrl(),
            'type' => $documentType,
        ]);
    }

    public function getStudentDocuments(Student $student): JsonResponse
    {
        $photo = $student->getFirstMediaUrl('photo');
        $birthCertificate = $student->getFirstMediaUrl('birth_certificate');
        $documents = $student->getMedia('documents')->map(fn ($media) => [
            'id' => $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'type' => $media->getCustomProperty('type'),
            'url' => $media->getUrl(),
            'size' => $media->size,
            'created_at' => $media->created_at->toISOString(),
        ]);

        return response()->json([
            'photo' => $photo ?: null,
            'birth_certificate' => $birthCertificate ?: null,
            'documents' => $documents,
        ]);
    }

    public function deleteStudentDocument(Request $request, Student $student, int $mediaId): JsonResponse
    {
        $media = $student->media()->find($mediaId);

        if (!$media) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        $media->delete();

        return response()->json(['message' => 'Document deleted successfully.']);
    }

    public function uploadUserPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();
        $user->addMediaFromRequest('photo')
            ->toMediaCollection('profile_photo');

        return response()->json([
            'message' => 'Profile photo uploaded successfully.',
            'url' => $user->getFirstMediaUrl('profile_photo'),
        ]);
    }

    public function uploadSchoolLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $school = School::find($request->user()->school_id);

        if (!$school) {
            return response()->json(['message' => 'School not found.'], 404);
        }

        $school->addMediaFromRequest('logo')
            ->toMediaCollection('logo');

        return response()->json([
            'message' => 'School logo uploaded successfully.',
            'url' => $school->getFirstMediaUrl('logo'),
        ]);
    }

    public function uploadSchoolBanner(Request $request): JsonResponse
    {
        $request->validate([
            'banner' => ['required', 'image', 'max:5120'],
        ]);

        $school = School::find($request->user()->school_id);

        if (!$school) {
            return response()->json(['message' => 'School not found.'], 404);
        }

        $school->addMediaFromRequest('banner')
            ->toMediaCollection('banner');

        return response()->json([
            'message' => 'School banner uploaded successfully.',
            'url' => $school->getFirstMediaUrl('banner'),
        ]);
    }

    public function uploadSchoolDocument(Request $request): JsonResponse
    {
        $request->validate([
            'document' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $school = School::find($request->user()->school_id);

        if (!$school) {
            return response()->json(['message' => 'School not found.'], 404);
        }

        $media = $school->addMediaFromRequest('document')
            ->usingName($request->input('name'))
            ->toMediaCollection('documents');

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'media_id' => $media->id,
            'url' => $media->getUrl(),
        ]);
    }
}
