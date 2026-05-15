<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_records', function (Blueprint $table) {
            $table->dropUnique('platform_records_section_record_id_unique');
            $table->unique(['section', 'store_id', 'record_id'], 'platform_records_section_store_record_unique');
        });
    }

    public function down(): void
    {
        Schema::table('platform_records', function (Blueprint $table) {
            $table->dropUnique('platform_records_section_store_record_unique');
            $table->unique(['section', 'record_id'], 'platform_records_section_record_id_unique');
        });
    }
};
