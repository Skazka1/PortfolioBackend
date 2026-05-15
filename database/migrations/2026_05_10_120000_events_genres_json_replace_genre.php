<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('events', 'genres')) {
            Schema::table('events', function (Blueprint $table) {
                $table->json('genres')->nullable()->after('location');
            });
        }

        if (Schema::hasColumn('events', 'genre')) {
            foreach (DB::table('events')->orderBy('id')->cursor() as $row) {
                $genres = [];
                if (! empty($row->genre)) {
                    $genres = [(string) $row->genre];
                }
                DB::table('events')->where('id', $row->id)->update([
                    'genres' => json_encode($genres, JSON_UNESCAPED_UNICODE),
                ]);
            }

            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('genre');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('events', 'genre')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('genre', 64)->nullable()->after('location');
            });
        }

        foreach (DB::table('events')->orderBy('id')->cursor() as $row) {
            $decoded = json_decode((string) ($row->genres ?? '[]'), true);
            $first = null;
            if (is_array($decoded) && $decoded !== []) {
                $first = (string) reset($decoded);
            }
            DB::table('events')->where('id', $row->id)->update(['genre' => $first]);
        }

        if (Schema::hasColumn('events', 'genres')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('genres');
            });
        }
    }
};
