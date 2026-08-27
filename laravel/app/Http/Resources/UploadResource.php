<?php

namespace App\Http\Resources;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Upload */
class UploadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // The authorized retrieve/preview endpoint — never a raw
            // MinIO/S3 URL (master prompt §25: no predictable public
            // URLs for private files).
            'url' => route('api.v1.uploads.show', $this->id),
            'originalName' => $this->original_name,
            'mimeType' => $this->mime_type,
            'sizeBytes' => $this->size_bytes,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
