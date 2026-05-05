<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('memory_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'memory_id']);
            $table->unique(['user_id', 'category_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('memory_id')->references('id')->on('memories')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement(
                'ALTER TABLE favorites ADD CONSTRAINT favorites_exactly_one_target_check CHECK ((memory_id IS NOT NULL AND category_id IS NULL) OR (memory_id IS NULL AND category_id IS NOT NULL))'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
