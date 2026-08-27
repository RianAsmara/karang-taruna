<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upload\StoreUploadRequest;
use App\Http\Resources\UploadResource;
use App\Models\Organization;
use App\Models\Upload;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UploadController extends Controller
{
    /**
     * The only Content-Types ever served inline — matches
     * StoreUploadRequest's `mimes`/`mimetypes` validation exactly.
     * Never trust the stored mime_type on its own when serving a file
     * back: a mismatch (which shouldn't happen given upload-time
     * validation, but costs nothing to guard against) falls back to a
     * forced download instead of ever rendering inline.
     */
    private const PREVIEWABLE_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];

    /**
     * One endpoint for every upload (jpg/jpeg/png/pdf) regardless of what
     * it's for — the caller gets back an id and an authorized retrieve/
     * preview URL, and decides what to do with them (attach the id
     * elsewhere, show the URL in an <Image>, etc).
     */
    public function store(StoreUploadRequest $request, Organization $organization): JsonResponse
    {
        $file = $request->file('file');
        $disk = config('filesystems.default');
        $path = $file->store("uploads/{$organization->id}", $disk);

        $upload = $organization->uploads()->create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        return (new UploadResource($upload))->response()->setStatusCode(201);
    }

    /**
     * Retrieve/preview — streamed inline (not forced download) so a
     * browser or mobile image/PDF viewer renders it directly, unlike
     * FinancialTransactionAttachmentController::download()'s forced
     * attachment disposition. Only for the known-safe types above;
     * anything else is served as an attachment with sniffing disabled,
     * never inline with a browser-guessable type.
     */
    public function show(Upload $upload): StreamedResponse
    {
        $this->authorize('view', $upload);

        $isPreviewable = in_array($upload->mime_type, self::PREVIEWABLE_TYPES, true);

        return Storage::disk($upload->disk)->response($upload->path, $upload->original_name, [
            'Content-Type' => $isPreviewable ? $upload->mime_type : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ], $isPreviewable ? 'inline' : 'attachment');
    }
}
