<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Attendance */
class AttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->membership->user->name,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'method' => $this->method->value,
            'methodLabel' => $this->method->label(),
            'checkedInAt' => $this->checked_in_at->toIso8601String(),
        ];
    }
}
