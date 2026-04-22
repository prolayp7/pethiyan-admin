<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'category_id' => $this->faq_category_id,
            'question'    => $this->question,
            'answer'      => $this->answer,
            'sort_order'  => $this->sort_order,
            'status'      => $this->status,
            'created_at'  => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'  => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}