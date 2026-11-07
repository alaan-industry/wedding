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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->unsignedTinyInteger('seats')->default(1);
            $table->string('status')->default('pending')->index();
            $table->unsignedTinyInteger('attending_count')->default(0);
            $table->json('guest_names')->nullable();
            $table->string('dietary_notes')->nullable();
            $table->string('song_request')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
