<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete()->unique();
            $table->foreignId('control_id')->constrained();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('place');
            $table->string('facilitator_name');
            $table->string('facilitator_document');
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
