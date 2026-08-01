<?php

declare(strict_types=1);

use App\Modules\Vehicles\Enums\FuelType;
use App\Modules\Vehicles\Enums\VehicleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            $table->string('plate', 15)->unique();
            $table->string('brand', 60);
            $table->string('model', 60);
            $table->smallInteger('year');

            $table->string('type', 32)->default(VehicleType::Car->value);
            $table->string('fuel_type', 32)->default(FuelType::Gasoline->value);
            $table->string('color', 40)->nullable();

            $table->string('vin', 40)->nullable()->unique();
            $table->string('engine_number', 40)->nullable();
            $table->unsignedInteger('mileage')->default(0);

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('brand');
            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
