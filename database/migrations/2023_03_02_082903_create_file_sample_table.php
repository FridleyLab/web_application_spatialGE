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
        Schema::create('file_sample', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('file_id');
            $table->foreign('file_id')->references('id')->on('files');

            $table->unsignedBigInteger('sample_id');
            $table->foreign('sample_id')->references('id')->on('samples');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_sample');
    }
};
