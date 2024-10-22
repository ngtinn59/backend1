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
        Schema::create('objectives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('resume_id')->nullable();
            $table->text('title');
            $table->unsignedBigInteger('jtype_id')->nullable();
            $table->unsignedBigInteger('jlevel_id')->nullable();
            $table->unsignedInteger('min_salary')->nullable();
            $table->unsignedInteger('max_salary')->nullable();

            $table->foreign('jtype_id')->references('id')->on('jtypes')->onDelete('cascade');
            $table->foreign('jlevel_id')->references('id')->on('jlevels')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objectives');
    }
};
