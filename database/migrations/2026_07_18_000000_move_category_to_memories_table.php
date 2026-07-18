<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memories', function (Blueprint $table): void {
            $table->uuid('category_id')->nullable()->after('user_id');
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });

        DB::table('memories')
            ->select('id')
            ->orderBy('id')
            ->chunk(500, function ($memories): void {
                foreach ($memories as $memory) {
                    $categoryId = DB::table('category_memory')
                        ->where('memory_id', $memory->id)
                        ->orderBy('created_at')
                        ->orderBy('category_id')
                        ->value('category_id');

                    if ($categoryId !== null) {
                        DB::table('memories')
                            ->where('id', $memory->id)
                            ->update(['category_id' => $categoryId]);
                    }
                }
            });

        Schema::dropIfExists('category_memory');
    }

    public function down(): void
    {
        Schema::create('category_memory', function (Blueprint $table): void {
            $table->uuid('category_id');
            $table->uuid('memory_id');
            $table->timestamps();
            $table->unique(['category_id', 'memory_id']);
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->foreign('memory_id')->references('id')->on('memories')->cascadeOnDelete();
        });

        $now = now();
        DB::table('memories')
            ->whereNotNull('category_id')
            ->orderBy('id')
            ->chunk(500, function ($memories) use ($now): void {
                DB::table('category_memory')->insert($memories->map(fn ($memory): array => [
                    'category_id' => $memory->category_id,
                    'memory_id' => $memory->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });

        Schema::table('memories', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
