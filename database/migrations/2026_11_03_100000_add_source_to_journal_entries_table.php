<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('source_type', 40)->nullable()->after('company_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->unique(['company_id', 'source_type', 'source_id'], 'journal_entries_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropUnique('journal_entries_source_unique');
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
