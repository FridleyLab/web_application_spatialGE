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
        Schema::create('task_stats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('task')->index();
            $table->float('cpu');
            $table->float('memory');
            $table->timestamp('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_stats');
    }
};
