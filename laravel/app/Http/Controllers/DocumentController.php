<?php

namespace App\Http\Controllers;

use App\Enums\DocumentCategory;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Models\Document;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Newest first inside each month group — mobile-screens.md §21.
     */
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [Document::class, $organization]);

        $documents = $organization->documents()
            ->with(['uploader:id,name', 'event:id,title'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'category' => $document->category->value,
                'categoryLabel' => $document->category->label(),
                'originalName' => $document->original_name,
                'sizeBytes' => $document->size_bytes,
                'uploaderName' => $document->uploader->name,
                'eventTitle' => $document->event?->title,
                'canDelete' => Auth::user()->can('delete', $document),
                'createdAt' => $document->created_at->toIso8601String(),
            ]);

        return Inertia::render('documents/index', [
            'documents' => $documents,
            'categories' => array_map(
                fn (DocumentCategory $c) => ['value' => $c->value, 'label' => $c->label()],
                DocumentCategory::cases(),
            ),
            'canCreate' => Auth::user()->isSecretaryOf($organization) || Auth::user()->isTreasurerOf($organization),
        ]);
    }

    /**
     * Who may create at all (any category) is secretary; a treasurer who
     * isn't also secretary is restricted to Laporan — the policy's
     * per-category check runs again server-side on store(), this only
     * gates whether the page is reachable and which categories the form
     * offers.
     */
    public function create(Organization $organization): Response
    {
        $isSecretary = Auth::user()->isSecretaryOf($organization);
        $isTreasurer = Auth::user()->isTreasurerOf($organization);
        abort_unless($isSecretary || $isTreasurer, 403);

        $categories = $isSecretary
            ? DocumentCategory::cases()
            : [DocumentCategory::Laporan];

        return Inertia::render('documents/create', [
            'categories' => array_map(
                fn (DocumentCategory $c) => ['value' => $c->value, 'label' => $c->label()],
                $categories,
            ),
            'events' => $organization->events()->orderByDesc('start_at')->get(['id', 'title']),
        ]);
    }

    public function store(StoreDocumentRequest $request, Organization $organization): RedirectResponse
    {
        $file = $request->file('file');
        $disk = config('filesystems.default');
        $path = $file->store("documents/{$organization->id}", $disk);

        $organization->documents()->create([
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

        return to_route('documents.index');
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return to_route('documents.index');
    }
}
