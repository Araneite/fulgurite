<?php

namespace App\Http\Controllers\API\Internal\Billing;

use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ModelNotFoundException;
use App\Exceptions\ModelNotTrashedException;
use App\Exceptions\RequestNotConfirmedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CustomerStoreRequest;
use App\Http\Requests\Billing\CustomerUpdateRequest;
use App\Http\Resources\API\Internal\BaseResource;
use App\Http\Resources\API\Internal\Billing\CustomerCollection;
use App\Http\Resources\API\Internal\Billing\CustomerResource;
use App\Models\Billing\Customer;
use App\Models\Billing\CustomerBillingProfile;
use App\Services\ActionLogger;
use App\Traits\AuthorizesApiRequests;
use App\Traits\HandlesIncludes;
use App\Traits\HasApiPagination;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    use HandlesIncludes, HasApiPagination, softDeletes, AuthorizesApiRequests;
    
    /**
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function index(Request $request) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "view", "internal.actions.customers.index", Customer::class);
        
        $query = Customer::query()->select(["id", "name", "slug", "legal_name", "website", "registration_number", "vat_number", "industry", "status"]);
        
        $this->applyIncludes($request, $query, [
            "relations"=> [
                "billingContacts",
                "billingProfile"
            ],
            "fields"=> ["created_at", "updated_at", "deleted_at"],            
            "aliases"=> [
                "contacts"=> ["billingContacts"],
                "profile"=> ["billingProfile"],
                "billing"=> ["billingProfile", "billingContacts"],
                "timestamps"=> ["created_at", "updated_at", "deleted_at"],
            ]
        ]);
        
        $customers = $query->apiList($request);
        
        return new CustomerCollection($customers);
    }
    
    /**
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function show(Request $request, $customerReq, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "view", "internal.actions.customers.show", Customer::class);
        
        $query = Customer::query()->select(["id", "name", "slug", "legal_name", "website", "registration_number", "vat_number", "industry", "status"]);
        
        $this->applyIncludes($request, $query, [
            "relations"=> [
                "billingContacts",
                "billingProfile"
            ],
            "fields"=> ["created_at", "updated_at", "deleted_at"],
            "aliases"=> [
                "contacts"=> ["billingContacts"],
                "profile"=> ["billingProfile"],
                "billing"=> ["billingProfile", "billingContacts"],
                "timestamps"=> ["created_at", "updated_at", "deleted_at"],
            ]
        ]);
        
        $model = is_numeric($customerReq) 
            ? $query->where("id", $customerReq)->first()
            : $query->where("slug", $customerReq)->first();
        
        if (!$model) {
            throw new ModelNotFoundException(
                trans('internal/errors.404.message'),
                [
                    "not_found"=> trans("internal/errors.404.detail", [
                        "model" => trans('internal/models.singular.customer'),
                        "requested"=> $customerReq
                    ]),
                ]
            );
        }
        
        $logger->info(
            action: "customer.show",
            description: "logs.customers.viewed",
            metadata: [
                'customer_id'=> $model->id
            ],
            target: $model
        );
        
        return (new CustomerResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans('internal/success.show.message'))
            ->setDetails([
                "show"=> trans('internal/success.show.detail', [
                    'model'=> trans('internal/models.singular.customer'),
                    "requested"=> $model->id
                ]),
            ]);
    }
    
    /**
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function store(CustomerStoreRequest $request, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "create", "internal.actions.customers.store", Customer::class);
        
        $customerData = $request->customerData();
        $customerBillingProfileData = $request->customerBillingProfileData();
        
        if (!empty($customerData)) {
            $model = Customer::create([
                "name" => $customerData["name"],
                "slug" => $customerData["slug"],
                "legal_name" => $customerData["legal_name"],
                "website" => $customerData["website"],
                "registration_number" => $customerData["registration_number"] ?? null,
                "vat_number" => $customerData["vat_number"] ?? null,
                "industry" => $customerData["industry"] ?? null,
                "status" => $customerData["status"] ?? "active",
                "metadata" => $customerBillingProfileData["customer_metadata"] ?? null,
                "created_at" => now(),
                "updated_at" => now(),
                "created_by" => $user->id,
                "updated_by" => $user->id,
            ]);
        }
        
        if ($model && !empty($customerBillingProfileData)) {
            $billingProfile =CustomerBillingProfile::create([
                "company_name"=> $customerBillingProfileData["company_name"] ?? null,
                "billing_email"=> $customerBillingProfileData["billing_email"],
                "address_line1"=> $customerBillingProfileData["address_line1"],
                "address_line2"=> $customerBillingProfileData["address_line2"] ?? null,
                "postal_code"=> $customerBillingProfileData["postal_code"],
                "city"=> $customerBillingProfileData["city"],
                "state"=> $customerBillingProfileData["state"] ?? null,
                "country"=> $customerBillingProfileData["country"],
                "currency"=> $customerBillingProfileData["currency"] ?? "USD",
                "language"=> $customerBillingProfileData["language"] ?? config('app.locale'),
                "payment_terms"=> $customerBillingProfileData["payment_terms"] ?? "net_30",
                "billing_reference"=> $customerBillingProfileData["billing_reference"] ?? null,
                "reverse_charge_vat"=> $customerBillingProfileData["reverse_charge_vat"] ?? 0,
                "external_billing_provider"=> $customerBillingProfileData["external_billing_provider"] ?? null,
                "external_billing_id"=> $customerBillingProfileData["external_billing_id"] ?? null,
                "metadata"=> $customerBillingProfileData["metadata"] ?? null,
                "customer_id"=> $model->id,
                "created_at" => now(),
                "updated_at" => now()
            ]);
        }
        
        $model->refresh()->load(["billingProfile"]);
        
        $logger->warning(
            action: "customer.create",
            description: "logs.customers.created",
            metadata: [
                "customer_id"=> $model->id,
                "billing_profile_id"=> $model->billingProfile->id,
            ],
            target: $model
        );
        
        return (new CustomerResource($model))
            ->success()
            ->setCode(201)
            ->setMessage(trans('internal/success.store.message'))
            ->setDetails([
                "create"=> trans('internal/success.store.details', [
                    "model"=> trans('internal/models.singular.customer')
                ])
            ]);
    }
    
    /**
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function update(CustomerUpdateRequest $request, int|string $customerReq, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "update", "internal.actions.customers.update", Customer::class);
        
        $model = $request->getTargetCustomer();
        
        if (!$model) {
            throw new ModelNotFoundException(
                trans("internal/errors.404.message"),
                [
                    "not_found"=> trans("internal/errors.404.detail", [
                        "model" => trans('internal/models.singular.customer'),
                        "requested" => $customerReq
                    ]),
                ]
            );
        }
        
        $customerData = $request->customerData();
        $customerBillingProfileData = $request->customerBillingProfileData();
        
        $billingProfile = $model->billingProfile;
        $customerChanges = [];
        $billingProfileChanges = [];
        
        if (!empty($customerData)) {
            $model->update($customerData);
            $customerChanges = $model->getChanges();
        }
        if (!empty($customerBillingProfileData)) {
            $billingProfile->update($customerBillingProfileData);
            $customerBillingProfileChanges =$billingProfile->getChanges();
        }
        
        $model->refresh()->load(["billingProfile", "billingContacts"]);
        
        
        $logger->warning(
            action: "customer.update",
            description: "logs.customers.updated",
            metadata: [
                "customer_id"=> $model->id,
                "data_changed"=> [
                    "customer"=> $customerChanges,
                    "billing_profile"=> $customerBillingProfileChanges
                ]
            ],
            target: $model
        );
        
        return (new CustomerResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans('internal/success.update.message'))
            ->setDetails([
                "update"=> trans('internal/success.update.detail', [
                    'model' => trans("internal/models.singular.customer"), 
                    'requested' => $customerReq
                ])
            ]);
    }
    
    /**
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function destroy(int|string $customerReq, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "delete", "internal.actions.customers.destroy", Customer::class);
        
        $query = Customer::query();
        
        $model = is_numeric($customerReq)
            ? $query->where('id', $customerReq)->first()
            : $query->where('slug', $customerReq)->first();
        
        if (!$model) {
            throw new ModelNotFoundException(
                trans("internal/errors.404.message"),
                [
                    "not_found"=> trans("internal/errors.404.detail", [
                        "model"=> trans('internal/models.singular.customer'),
                        "requested"=> $customerReq
                    ]),
                ]
            );
        }
        
        $model->billingProfile->delete();
        $model->delete();
        
        $logger->warning(
            action: "customer.destroy",
            description: "logs.customers.deleted",
            metadata: [
                "customer_id"=> $model->id,
            ],
            target: $model
        );
        
        return (new CustomerResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans('internal/success.destroy.message'))
            ->setDetails([
                "delete"=> trans('internal/success.destroy.detail', [
                    "model"=> trans('internal/models.singular.customer'),
                    "requested"=> $customerReq
                ]),
            ]);
    }
    
    /**
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function restore(int|string $customerReq, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "restore", "internal.actions.customers.restore", Customer::class);
        
        $query = Customer::query();
        $model = is_numeric($customerReq)
            ? $query->withTrashed()
                ->where('id', $customerReq)
                ->first()
            : $query->withTrashed()
                ->where('slug', $customerReq)
                ->first();
        
        if (!$model->trashed()) {
            throw new ModelNotTrashedException(
                trans("internal/errors.not_trashed.message"),
                [
                    "not_trashed"=> trans("internal/errors.not_trashed.detail", [
                        "model"=> trans('internal/models.singular.customer'),
                        "requested"=> $customerReq
                    ]),
                ]
            );
        }
        if (!$model) {
            throw new ModelNotFoundException(
                trans("internal/errors.404.message"),
                [
                    "not_found"=> trans("internal/errors.404.detail", [
                        "model"=> trans('internal/models.singular.customer'),
                        "requested"=>$customerReq
                    ]),
                ]
            );
        }
        
        $model->billingProfile->restore();
        $model->restore();
        
        $logger->info(
            action: 'customer.restore',
            description: "logs.customers.restored",
            metadata: [
                "customer_id"=> $model->id,
            ],
            target: $model
        );
        
        return (new CustomerResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans('internal/success.restore.message'))
            ->setDetails([
                "restore"=> trans('internal/success.restore.detail', [
                    "model"=> trans('internal/models.singular.customer'),
                    "requested"=>$customerReq
                ]),
            ]);
        
    }
    
    /**
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function forceDeleteCustomer(Request $request, int|string $customerReq, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "forceDelete", "internal.actions.customers.destroy", Customer::class);
        
        $query = Customer::query();
        
        $model = is_numeric($customerReq)
            ? $query->withTrashed()
                ->where('id', $customerReq)
                ->first()
            : $query->withTrashed()
                ->where('slug', $customerReq)
                ->first();
        
        if (!$model) {
            throw new ModelNotFoundException(
                trans("internal/errors.404.message"),
                [
                    "not_found"=> trans("internal/errors.404.detail", [
                        "model"=> trans('internal/models.singular.customer'),
                        'requested'=>$customerReq
                    ])
                ]
            );
        }
        
        $confirmation = (bool)$request->confirmation;
        
        if (!$confirmation) {
            throw new RequestNotConfirmedException(
                trans("internal/errors.confirmation.message"),
                [
                    "confirmation_needed"=> trans("internal/errors.confirmation.detail")
                ]
            );
        }
        
        $contacts = $model->billingContacts;
        foreach ($contacts as $contact) {
            $contact->forceDelete();
        }        
        $model->billingProfile->forceDelete();
        $model->forceDelete();
        
        $logger->warning(
            action: "customer.delete.force",
            description: "logs.customers.force_deleted",
            metadata: [
                "customer_id"=> $model->id,
            ],
            target: $model
        );
        
        return (new CustomerResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans('internal/success.force_delete.message'))
            ->setDetails([
                "force_delete"=> trans('internal/success.force_delete.detail', [
                    "model"=> trans("internal/models.singular.customer"),
                    "requested"=> $customerReq
                ]),
            ]);
    }
}
