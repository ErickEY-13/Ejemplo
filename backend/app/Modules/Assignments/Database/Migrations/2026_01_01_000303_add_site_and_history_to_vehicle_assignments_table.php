<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_assignments', function (Blueprint $table) {
            $table->dropUnique(['vehicle_id']);

            $table->foreignId('site_id')->nullable()->after('vehicle_id')->constrained('sites')->nullOnDelete();
            $table->timestamp('ended_at')->nullable()->after('assigned_at');

            $table->index(['vehicle_id', 'ended_at']);
        });

        // `person_id` deja de ser obligatorio: un vehículo puede quedar en una
        // sede sin responsable. SQL nativo para no añadir doctrine/dbal solo
        // para este `change()`.
        DB::statement('ALTER TABLE vehicle_assignments ALTER COLUMN person_id DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE vehicle_assignments ALTER COLUMN person_id SET NOT NULL');

        Schema::table('vehicle_assignments', function (Blueprint $table) {
            $table->dropIndex(['vehicle_id', 'ended_at']);
            $table->dropColumn('ended_at');
            $table->dropConstrainedForeignId('site_id');
        });

        Schema::table('vehicle_assignments', function (Blueprint $table) {
            $table->unique('vehicle_id');
        });
    }
};
