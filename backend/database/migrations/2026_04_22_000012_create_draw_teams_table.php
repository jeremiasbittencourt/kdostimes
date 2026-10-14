<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('draw_teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('draw_id')->constrained()->cascadeOnDelete();
            $table->string('name', 32);
            $table->unsignedSmallInteger('total_rating')->default(0);
            $table->timestamps();

            $table->unique(['draw_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draw_teams');
    }
};
