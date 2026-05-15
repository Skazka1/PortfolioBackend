<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'created_by_user_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_user_id')->nullable()->after('supervisor_user_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('created_by_user_id', 'projects_created_by_user_id_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('projects', 'created_by_user_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign('projects_created_by_user_id_foreign');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('created_by_user_id');
        });
    }
};
