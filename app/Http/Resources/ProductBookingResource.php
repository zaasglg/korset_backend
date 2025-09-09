<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'address' => $this->product->address,
                'category' => $this->product->category->name ?? null,
                'city' => $this->product->city->name ?? null,
            ],
            'user' => $this->when($this->relationLoaded('user'), [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone_number' => $this->user->phone_number,
            ]),
            'status' => $this->status,
            'status_name' => $this->status_name,
            'commission_amount' => $this->commission_amount,
            'formatted_commission_amount' => $this->formatted_commission_amount,
            'payment_reference' => $this->payment_reference,
            'booked_at' => $this->booked_at,
            'expires_at' => $this->expires_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
