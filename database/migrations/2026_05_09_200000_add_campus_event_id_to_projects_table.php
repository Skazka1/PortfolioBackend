<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'campus_event_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('campus_event_id')->nullable()->after('created_by_user_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('campus_event_id', 'projects_campus_event_id_foreign')
                ->references('id')
                ->on('events')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('projects', 'campus_event_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign('projects_campus_event_id_foreign');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('campus_event_id');
        });
    }
};
