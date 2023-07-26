<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('projects', 'tag')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('project_platform_id')->nullable()->after('current_step');
                $table->foreign('project_platform_id')->references('id')->on('project_platforms');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropColumns('projects',['project_platform_id']);
    }
};
