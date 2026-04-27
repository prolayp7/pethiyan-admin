<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->product_id,
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'rating'     => $this->rating,
            'status'     => $this->status ?? 'approved',
            'title'      => $this->title,
            'slug'       => $this->slug,
            'comment'    => $this->comment,
            'review_images' => $this->review_images,
            'reviewer_name' => $this->reviewer_name,
            'user'       => [
                'id'   => $this->user->id ?? null,
                'name' => $this->user->name ?? $this->reviewer_name ?? null,
            ],
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
