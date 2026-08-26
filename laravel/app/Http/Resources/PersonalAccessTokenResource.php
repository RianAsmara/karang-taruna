<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/** @mixin PersonalAccessToken */
class PersonalAccessTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentToken = $request->user()?->currentAccessToken();

        return [
            'id' => $this->id,
            'deviceName' => $this->name,
            'lastUsedAt' => $this->last_used_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
            'isCurrent' => $currentToken !== null && $currentToken->id === $this->id,
        ];
    }
}
