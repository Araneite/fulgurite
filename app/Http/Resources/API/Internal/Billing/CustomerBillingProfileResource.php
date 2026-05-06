<?php

namespace App\Http\Resources\API\Internal\Billing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerBillingProfileResource extends JsonResource
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
            "company_name"=> $this->when($this->company_name !== null, $this->company_name),
            "address"=> [
                "line1"=> $this->address_line1,
                "line2"=> $this->when($this->address_line2 !== null, $this->address_line2),
                "postal_code"=> $this->postal_code,
                "city"=> $this->city,
                "state"=> $this->when($this->state !== null, $this->state),
                "country"=> $this->country,
            ],
            "currency"=> $this->currency,
            "language"=> $this->language,
            "billing_details"=> [
                "payment_terms"=> $this->payment_terms,
                "billing_reference"=> $this->billing_reference,
                "reverse_charge_vat"=> $this->reverse_charge_vat,
                "external_billing_provider"=> $this->external_billing_provider,
                "external_billing_id"=> $this->external_billing_id
            ],
            "metadata"=> $this->when($this->metadata !== null || !empty($this->metadata), $this->metadata)
        );
    }
}
