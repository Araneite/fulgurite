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
        Schema::create('fg_hosts', function (Blueprint $table) {
            $table->id();

            // Details
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('hostname');
            $table->integer('port');
            $table->string('user');

            // Restore
            $table->string('restore_managed_root');
            $table->boolean('restore_origin_enabled')->default(false);

            // Relations
            $table->unsignedBigInteger('ssh_key_id');
            $table->unsignedBigInteger('sudo_pass_ref');

            $table->foreign('ssh_key_id')->references('id')->on('fg_ssh_keys');
            $table->foreign('sudo_pass_ref')->references('id')->on('fg_secret_refs');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_hosts');
    }
};
