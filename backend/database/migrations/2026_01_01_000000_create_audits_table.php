<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            
            $table->morphs('auditable'); // auditable_type, auditable_id
            $table->string('event', 50); // created, updated, deleted
            
            // Usamos json para guardar los cambios
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            
            // Usuario que realizó el cambio (opcional, por si luego agregas auth)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
