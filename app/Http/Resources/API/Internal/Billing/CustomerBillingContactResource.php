<?php

namespace App\Http\Resources\API\Internal\Billing;

use App\Http\Resources\API\Internal\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerBillingContactResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array(
            "id"=> $this->id,
            "name"=> $this->name,
            "email"=> $this->email,
            "phone"=> [
                "extension"=> $this->phone_extension ?? 1,
                "number"=> $this->phone_number ?? null,
            ],
            "role"=> $this->role,
            "is_primary"=> $this->is_primary,
            "receives_invoices"=> $this->receives_invoices,
            "receives_payment_reminders"=> $this->receives_payment_reminders,
            "language"=> $this->language,
            "metadata"=> $this->when($this->metadata !== null && !empty($this->metadata), $this->metadata)
        );
    }
}
