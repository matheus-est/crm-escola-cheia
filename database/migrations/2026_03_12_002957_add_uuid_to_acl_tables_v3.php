<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        Schema::table('module_actions', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        if (Schema::hasColumn('users', 'uuid')) {
            DB::table('users')
                ->whereNull('uuid')
                ->orWhere('uuid', '')
                ->orderBy('id')
                ->each(fn ($r) => DB::table('users')
                    ->where('id', $r->id)
                    ->update(['uuid' => (string) Str::uuid()])
                );
        }

        if (Schema::hasColumn('roles', 'uuid')) {
            DB::table('roles')
                ->whereNull('uuid')
                ->orWhere('uuid', '')
                ->orderBy('id')
                ->each(fn ($r) => DB::table('roles')
                    ->where('id', $r->id)
                    ->update(['uuid' => (string) Str::uuid()])
                );
        }

        if (Schema::hasColumn('permissions', 'uuid')) {
            DB::table('permissions')
                ->whereNull('uuid')
                ->orWhere('uuid', '')
                ->orderBy('id')
                ->each(fn ($r) => DB::table('permissions')
                    ->where('id', $r->id)
                    ->update(['uuid' => (string) Str::uuid()])
                );
        }

        if (Schema::hasColumn('modules', 'uuid')) {
            DB::table('modules')
                ->whereNull('uuid')
                ->orWhere('uuid', '')
                ->orderBy('id')
                ->each(fn ($r) => DB::table('modules')
                    ->where('id', $r->id)
                    ->update(['uuid' => (string) Str::uuid()])
                );
        }

        if (Schema::hasColumn('module_actions', 'uuid')) {
            DB::table('module_actions')
                ->whereNull('uuid')
                ->orWhere('uuid', '')
                ->orderBy('id')
                ->each(fn ($r) => DB::table('module_actions')
                    ->where('id', $r->id)
                    ->update(['uuid' => (string) Str::uuid()])
                );
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('module_actions', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
