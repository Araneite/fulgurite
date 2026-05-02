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
        Schema::create('fg_contacts', function (Blueprint $table) {
            $table->id();

            // Contact information
            $table->string("first_name")->nullable();
            $table->string("last_name")->nullable();
            $table->integer("phone")->nullable();
            $table->integer("phone_extension")->nullable();
            $table->string("job_title")->nullable();

            // Relations
            $table->unsignedBigInteger("user_id");

            $table->foreign('user_id')->references('id')->on('fg_users')->onDelete(null);

            $table->timestamps();
            
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_contacts');
    }
};
