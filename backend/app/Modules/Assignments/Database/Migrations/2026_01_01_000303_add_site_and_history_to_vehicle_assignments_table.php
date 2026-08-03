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
        // Clean up rows that would violate the old constraints before reverting.
        // This migration enabled multiple rows per vehicle_id and nullable person_id,
        // so we need to handle both when rolling back.

        // Delete rows with NULL person_id (the old schema required person_id to be NOT NULL)
        DB::table('vehicle_assignments')->whereNull('person_id')->delete();

        // For vehicle_ids with multiple rows, keep only the most recent by assigned_at
        // and delete the rest (the old schema had a unique constraint on vehicle_id)
        $duplicateVehicleIds = DB::table('vehicle_assignments')
            ->selectRaw('vehicle_id')
            ->groupBy('vehicle_id')
            ->havingRaw('count(*) > 1')
            ->pluck('vehicle_id');

        foreach ($duplicateVehicleIds as $vehicleId) {
            // Find the row to keep: most recently assigned_at
            $rowToKeep = DB::table('vehicle_assignments')
                ->where('vehicle_id', $vehicleId)
                ->orderByDesc('assigned_at')
                ->first(['id']);

            // Delete all other rows for this vehicle
            if ($rowToKeep) {
                DB::table('vehicle_assignments')
                    ->where('vehicle_id', $vehicleId)
                    ->where('id', '!=', $rowToKeep->id)
                    ->delete();
            }
        }

        // Now safe to enforce the old constraints
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
