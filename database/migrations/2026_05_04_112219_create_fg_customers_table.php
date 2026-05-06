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
        Schema::create('fg_customers', function (Blueprint $table) {
            $table->id();
            
            $table->string('name');
            $table->string('slug')->unique();
            
            $table->string('legal_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('vat_number')->nullable();
            
            $table->string('website')->nullable();
            $table->string('industry')->nullable();
            
            $table->string('status')->default('active'); // active, inactive, archived
            
            $table->json('metadata')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            $table->foreign('created_by')->references('id')->on('fg_users');
            $table->foreign('updated_by')->references('id')->on('fg_users');
            $table->foreign('deleted_by')->references('id')->on('fg_users');
            
            $table->softDeletes();
            
            $table->index("status");
            $table->index("slug");
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_customers');
    }
};
