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
        Schema::table('audit_logins', function (Blueprint $table) {
            $table->string('login_type')->nullable()->after('ip_address');
            $table->string('browser')->nullable()->after('login_type');
            $table->string('browser_version')->nullable()->after('browser');
            $table->string('os')->nullable()->after('browser_version');
            $table->string('device')->nullable()->after('os');
            $table->string('session_id')->nullable()->after('device');
            $table->timestamp('logged_out_at')->nullable()->after('logged_in_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logins', function (Blueprint $table) {
            $table->dropColumn([
                'browser',
                'browser_version',
                'os',
                'device',
                'session_id',
                'logged_out_at',
            ]);
        });
    }
};
