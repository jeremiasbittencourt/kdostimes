<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pelada_voting_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pelada_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voting_category_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['pelada_id', 'voting_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelada_voting_settings');
    }
};
