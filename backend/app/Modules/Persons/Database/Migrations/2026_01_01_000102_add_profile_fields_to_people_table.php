<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('photo_path', 500)->nullable()->after('notes');

            // Datos laborales
            $table->string('area', 40)->nullable()->after('site');
            $table->string('position', 100)->nullable()->after('area');
            $table->string('contract_type', 30)->nullable()->after('position');
            $table->date('hire_date')->nullable()->after('contract_type');
            $table->string('work_shift', 20)->nullable()->after('hire_date');

            // Datos personales adicionales
            $table->string('marital_status', 20)->nullable()->after('gender');
            $table->string('education_level', 20)->nullable()->after('marital_status');
            $table->unsignedSmallInteger('children_count')->nullable()->after('education_level');

            // Contacto de emergencia
            $table->string('emergency_contact_name', 200)->nullable()->after('phone');
            $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name');

            // Datos administrativos
            $table->string('ruc', 11)->nullable()->after('address');
            $table->string('pension_system', 20)->nullable()->after('ruc');

            // Índices para filtros
            $table->index('area');
            $table->index('contract_type');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex(['area']);
            $table->dropIndex(['contract_type']);

            $table->dropColumn([
                'photo_path',
                'area',
                'position',
                'contract_type',
                'hire_date',
                'work_shift',
                'marital_status',
                'education_level',
                'children_count',
                'emergency_contact_name',
                'emergency_contact_phone',
                'ruc',
                'pension_system',
            ]);
        });
    }
};
