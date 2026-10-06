<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A 410 "gone" redirect has no destination — it's not a redirect at all,
     * just a deliberate "this no longer exists" response — so `to` can no
     * longer be required at the database level.
     */
    public function up(): void
    {
        Schema::table('redirects', function (Blueprint $table) {
            $table->string('to')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('redirects', function (Blueprint $table) {
            $table->string('to')->nullable(false)->change();
        });
    }
};
