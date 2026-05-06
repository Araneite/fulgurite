<?php

namespace App\Http\Resources\API\Internal\Billing;

use App\Http\Resources\API\Internal\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends BaseResource
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
            "slug"=> $this->slug,
            "legal_name"=> $this->legal_name,
            "registration_number"=> $this->registration_number,
            "vat_number"=> $this->vat_number,
            "website"=> $this->website,
            "industry"=> $this->industry,
            "status"=> $this->when(
                array_key_exists('status', $this->resource->getAttributes()),
                $this->status
            ),
            "metadata" => $this->when(
                array_key_exists('metadata', $this->resource->getAttributes()),
                $this->metadata
            ),
            "timestamps"=> $this->when(
                array_key_exists('created_at', $this->resource->getAttributes())
                || array_key_exists('updated_at', $this->resource->getAttributes())
                || array_key_exists('deleted_at', $this->resource->getAttributes()),
                [
                    "created_at" => $this->created_at,
                    "updated_at" => $this->updated_at,
                    "deleted_at" => $this->deleted_at,
                ]
            ),
            "contacts"=> $this->whenLoaded("billingContacts", function () {
                return new CustomerBillingContactCollection($this->billingContacts);
            }),
            "profile"=> $this->whenLoaded("billingProfile", function () {
                return new CustomerBillingProfileResource($this->billingProfile);
            })
        );
    }
}
