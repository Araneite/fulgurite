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
        Schema::create('fg_ssh_keys', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('description')->nullable();
            $table->string('public_key');
            $table->string('checksum_public_key');

            // Relations
            $table->unsignedBigInteger('private_key_ref');
            $table->foreign('private_key_ref')->references('id')->on('fg_secret_refs');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_ssh_keys');
    }
};
