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
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->text('title');
            $table->json('goals');
            $table->json('resources');
            $table->text('summary');
            $table->string('image');
            $table->text('image_analyse');
            $table->integer('summary_tokens_in');
            $table->integer('summary_tokens_out');
            $table->integer('summary_tokens_total');
            $table->integer('image_analyse_tokens_in');
            $table->integer('image_analyse_tokens_out');
            $table->integer('image_analyse_tokens_total');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
