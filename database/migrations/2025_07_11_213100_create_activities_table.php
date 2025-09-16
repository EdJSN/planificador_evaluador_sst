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
        Schema::create('activities', function (Blueprint $table) { //método pra ccreación de tablas
            $table->id(); 
            $table->string('thematic_axis', 500);
            $table->string('topic', 500);
            $table->string('objective', 500);
            $table->string('place')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('facilitator')->nullable();
            $table->string('facilitator_document')->nullable();
            $table->float('duration')->nullable();
            $table->integer('number_participants')->nullable();
            $table->date('estimated_date');
            $table->string('evaluation_methods')->nullable();
            $table->string('resources')->nullable();
            $table->string('budget')->nullable();
            $table->char('states', 1);
            $table->string('efficacy_evaluation')->nullable();
            $table->date('efficacy_evaluation_date')->nullable();
            $table->string('responsible')->nullable();
            $table->text('observations')->nullable();
            $table->integer('coverage')->nullable();
            $table->foreignId('control_id')->nullable()->constrained('controls')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
