<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'year_of_graduation')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('year_of_graduation');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'year_of_graduation')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('year_of_graduation')->nullable()->index();
        });
    }
};
