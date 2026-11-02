<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('staff')->after('email');
            $table->boolean('is_active')->default(true)->after('role');
        });
        // Existing users are the original system owners; preserve their access.
        DB::table('users')->update(['role'=>'admin']);
    }
    public function down(): void { Schema::table('users', function (Blueprint $table) { $table->dropColumn(['role','is_active']); }); }
};
