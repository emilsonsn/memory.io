<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('color')->nullable()->after('description');
        });

        Schema::table('memories', function (Blueprint $table): void {
            $table->string('color')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('color');
        });

        Schema::table('memories', function (Blueprint $table): void {
            $table->dropColumn('color');
        });
    }
};
