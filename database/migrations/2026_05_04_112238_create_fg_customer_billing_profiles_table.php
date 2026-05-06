<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fg_customer_billing_profiles', function (Blueprint $table) {
            $table->id();
            
            $table->string('company_name')->nullable();
            $table->string('billing_email');
            
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('postal_code');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country', 2); // FR, BE, US
            
            $table->string('currency', 3)->default("USD"); // EUR, USD
            $table->string('language', 5)->default("en_US"); // fr, en, fr_FR, en_US
            
            $table->string('payment_terms')->default('net_30'); // due_on_receipt, net_15, net_30, net_45
            $table->string('billing_reference')->nullable(); // customer reference / PO number
            $table->boolean('reverse_charge_vat')->default(false);
            
            $table->string('external_billing_provider')->nullable(); // Stripe, Paypal, Pennylane
            $table->string('external_billing_id')->nullable();
            
            $table->json('metadata')->nullable();
            
            $table->unsignedBigInteger("customer_id");
            
            $table->unique('customer_id');
            $table->index("billing_email");
            $table->index(["external_billing_provider", "external_billing_id"], 'fg_cbp_provider_id_idx');
            
            $table->softDeletes();
            
            // Relations
            $table->foreign('customer_id')->references('id')->on('fg_customers')->onDelete('cascade');
            
            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_customer_billing_profiles');
    }
};
