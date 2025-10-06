<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passkeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->longText('credential_id');
            $table->string('credential_hash', 128)->unique();
            $table->longText('public_key');
            $table->unsignedBigInteger('counter')->default(0);
            $table->json('transports')->nullable();
            $table->string('device_type')->nullable();
            $table->boolean('backed_up')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passkeys');
    }
};
