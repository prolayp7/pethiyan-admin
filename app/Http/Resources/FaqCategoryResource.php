<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FaqCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'icon'       => $this->icon,
            'sort_order' => $this->sort_order,
            'status'     => $this->status,
        ];
    }
}
