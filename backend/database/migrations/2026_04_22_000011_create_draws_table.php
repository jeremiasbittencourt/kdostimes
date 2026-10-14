<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('draws', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pelada_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->boolean('published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['pelada_session_id', 'published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draws');
    }
};
