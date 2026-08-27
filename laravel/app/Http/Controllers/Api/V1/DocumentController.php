<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Document::class, $organization]);

        $documents = $organization->documents()
            ->with(['uploader:id,name', 'event:id,title'])
            ->orderByDesc('created_at')
            ->get();

        return DocumentResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request, Organization $organization): JsonResponse
    {
        $file = $request->file('file');
        $disk = config('filesystems.default');
        $path = $file->store("documents/{$organization->id}", $disk);

        $document = $organization->documents()->create([
            'title' => $request->string('title')->value(),
            'category' => $request->string('category')->value(),
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'event_id' => $request->string('event_id')->value() ?: null,
            'uploaded_by' => Auth::id(),
        ]);

        return (new DocumentResource($document->load(['uploader:id,name', 'event:id,title'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Document $document): JsonResource
    {
        $this->authorize('view', $document);

        return new DocumentResource($document->load(['uploader:id,name', 'event:id,title']));
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return response()->json(null, 204);
    }
}
