<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'amount' => $this->amount,
            'max_memories' => $this->max_memories,
            'max_categories' => $this->max_categories,
            'can_export' => $this->can_export,
            'can_use_ai' => $this->can_use_ai,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
