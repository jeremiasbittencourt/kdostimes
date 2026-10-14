<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pelada_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pelada_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('token', 80)->unique();
            $table->timestamp('expires_at');
            $table->unsignedInteger('use_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();

            $table->index(['pelada_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelada_invites');
    }
};
