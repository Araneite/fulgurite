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
        Schema::create('fg_projects', function (Blueprint $table) {
            $table->id();
            
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            
            $table->boolean('status_page_enabled')->default(false);
            
            $table->unsignedBigInteger('customer_id')->nullable()->default(null);
            
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable()->default(null);
            $table->unsignedBigInteger('deleted_by')->nullable()->default(null);
            
            $table->foreign('customer_id')->references('id')->on('fg_customers')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('fg_users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('fg_users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('fg_users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_projects');
    }
};
