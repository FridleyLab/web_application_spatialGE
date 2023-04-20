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
        Schema::create('project_genes', function (Blueprint $table) {
            $table->id();

            $table->string('gene', 255);

            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')->references('id')->on('projects');

            $table->string('context', 255);

            $table->unique(['gene', 'project_id', 'context']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_genes');
    }
};
