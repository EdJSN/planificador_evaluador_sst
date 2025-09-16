<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_id')->nullable()->constrained();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('facilitator_signature_path');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_closures');
    }
};
