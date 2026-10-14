<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ranking_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pelada_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voting_category_id')->constrained()->cascadeOnDelete();
            $table->integer('total_points')->default(0);
            $table->unsignedInteger('wins_count')->default(0);
            $table->timestamp('last_computed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['pelada_id', 'user_id', 'voting_category_id'],
                'ranking_unique_entry'
            );
            $table->index(['pelada_id', 'total_points']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_entries');
    }
};
