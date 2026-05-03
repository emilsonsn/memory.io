<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'type' => $this->type?->value,
            'seen' => (bool) $this->seen,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
