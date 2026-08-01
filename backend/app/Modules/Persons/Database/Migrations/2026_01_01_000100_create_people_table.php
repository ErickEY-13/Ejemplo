<?php

declare(strict_types=1);

use App\Modules\Persons\Enums\DocumentType;
use App\Modules\Persons\Enums\Gender;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();

            $table->string('document_type', 32)->default(DocumentType::NationalId->value);
            $table->string('document_number', 32);

            $table->string('first_name', 100);
            $table->string('last_name', 100);

            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->default(Gender::Undisclosed->value);

            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address', 255)->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Un mismo número puede repetirse entre tipos de documento
            // distintos, pero no dentro del mismo tipo.
            $table->unique(['document_type', 'document_number']);
            $table->index('last_name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
