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
        if (!Schema::hasColumn('tasks', 'attempts')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->smallInteger('attempts')->default(1)->after('completed');
            });
        }

        if (!Schema::hasColumn('tasks', 'payload')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->mediumText('payload')->nullable()->after('attempts');
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
        Schema::dropColumns('tasks',['attempts', 'payload']);
    }
};
