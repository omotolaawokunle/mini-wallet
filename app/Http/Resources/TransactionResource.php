<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'sender' => $this->whenLoaded('sender', new UserResource($this->sender)),
            'receiver' => $this->whenLoaded('receiver', new UserResource($this->receiver)),
            'amount' => $this->amount,
            'commission_fee' => $this->commission_fee,
            'type' => $this->when(
                $request->user()?->id === $this->sender_id,
                'Debit',
                $request->user()?->id === $this->receiver_id ? 'Credit' : null
            ),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
