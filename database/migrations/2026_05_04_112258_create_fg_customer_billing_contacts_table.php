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
        Schema::create('fg_customer_billing_contacts', function (Blueprint $table) {
            $table->id();
            
            $table->string("name");
            $table->string("email");
            $table->integer("phone_number")->nullable();
            $table->integer("phone_extension")->nullable();
            
            $table->string("role")->nullable(); // accounting, finance_manager, procurement... 
            
            $table->boolean("is_primary")->default(false);
            $table->boolean("receives_invoices")->default(true);
            $table->boolean("receives_payment_reminders")->default(true);
            
            $table->string("language", 5)->nullable();
            
            $table->json("metadata")->nullable();
            
            $table->unsignedBigInteger("customer_id");
            
            $table->index("email");
            $table->index(["customer_id", "is_primary"]);
            
            $table->foreign("customer_id")->references("id")->on("fg_customers");
            
            $table->timestamps();
            
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_customer_billing_contacts');
    }
};
