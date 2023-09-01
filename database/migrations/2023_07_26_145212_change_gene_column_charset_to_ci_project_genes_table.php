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
        if (Schema::hasColumn('project_genes', 'gene')) {
            Schema::table('project_genes', function (Blueprint $table) {
                $table->string('gene', 255)->charset('utf8')->collation('UTF8_BIN')->change();
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
        Schema::dropColumns('users',['is_admin']);
    }
};
