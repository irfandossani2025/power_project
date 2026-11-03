<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Production uses legacy MariaDB; altering the enum directly avoids unsupported JSON/DDL helpers.
        if (DB::getDriverName() === 'mysql') DB::statement("ALTER TABLE import_batches MODIFY type ENUM('customers','suppliers','items') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') DB::statement("ALTER TABLE import_batches MODIFY type ENUM('customers','items') NOT NULL");
    }
};
