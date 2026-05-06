<?php

namespace App\Http\Controllers\API\Internal\Billing;

use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CustomerContactStoreRequest;
use App\Http\Resources\API\Internal\Billing\CustomerBillingContactResource;
use App\Models\Billing\CustomerBillingContact;
use App\Services\ActionLogger;
use App\Traits\AuthorizesApiRequests;
use App\Traits\HandlesIncludes;
use App\Traits\HasApiPagination;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerContactController extends Controller
{
    use HandlesIncludes, HasApiPagination, softDeletes, AuthorizesApiRequests;
    
    public function store(CustomerContactStoreRequest $request, ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "create", "internal.actions.customers.contacts.create", CustomerContactController::class);
        
        $contactData = $request->getContactData();
        
        if (!empty($contactData)) {
            $model = CustomerBillingContact::create([
                'name'=> $contactData['name'],
                "email"=> $contactData['email'],
                "phone_extension"=> $contactData['phone_extension'],
                "phone_number"=> $contactData['phone_number'],
                "role"=> $contactData['role'],
                "is_primary"=> $contactData['is_primary'],
                "receives_invoices"=> $contactData['receives_invoices'],
                "receives_payment_reminders"=> $contactData['receives_payment_reminders'],
                "language"=> $contactData['language'],
                "metadata"=> $contactData['metadata'],
                "customer_id"=> $contactData['customer_id'],
            ]);
        }
        
        $model->refresh()->load("customer");
        
        $logger->info(
            action: "billing_contact.create",
            description: "logs.customers.contacts.create",
            metadata: $contactData,
            target: $model
        );
        
        return (new CustomerBillingContactResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans("internal/success.store.message"))
            ->setDetails([
                'create' => trans("internal/success.store.details", [
                    "model" => trans("internal/models.singular.billing_contact")
                ]),
            ]);
    }
    
    /**
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function destroy(Request $request, int $id,ActionLogger $logger) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "delete", "internal.actions.users.delete.message", $user);
        
        $query = CustomerBillingContact::query();
        
        $model = $query->find($id);
        
        if (!$model) {
            throw new ModelNotFoundException(
                trans("internal/errors.404.message"),
                [
                    "not_found" => trans("internal/errors.404.detail", [
                        "model" => trans("internal/models.singular.billing_contact"),
                        "requested"=> $id
                    ])
                ]
            );
        }
    }
}
