<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Database\Seeders;

use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Enums\FuelType;
use App\Modules\Vehicles\Enums\VehicleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

/**
 * Lo ejecuta automáticamente Database\Seeders\DatabaseSeeder.
 * No depende de Faker: usa datos fijos para funcionar en producción.
 */
class VehiclesSeeder extends Seeder
{
    public function run(): void
    {
        if (Vehicle::query()->exists()) {
            $this->command?->info('Módulo Vehículos: ya hay datos, se omite el seeder.');
            return;
        }

        $now = now()->toDateTimeString();
        
        // Limpiar el directorio de fotos de vehículos en storage/app/public/vehicles
        Storage::disk('public')->deleteDirectory('vehicles');
        Storage::disk('public')->makeDirectory('vehicles');

        $vehicles = [
            // Activos
            ['plate' => 'AQV-872', 'brand' => 'Toyota',     'model' => 'Hilux',    'year' => 2019, 'type' => VehicleType::Truck->value,      'fuel_type' => FuelType::Diesel->value,   'color' => 'Blanco',  'vin' => 'JT3HP10V0X0123456', 'engine_number' => 'TN123456', 'mileage' => 85000,  'is_active' => true,  'notes' => null],
            ['plate' => 'BGX-697', 'brand' => 'Toyota',     'model' => 'Corolla',  'year' => 2021, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Plata',   'vin' => '2T1BURHE0JC123457', 'engine_number' => 'ZR234567', 'mileage' => 32000,  'is_active' => true,  'notes' => null],
            ['plate' => 'BJP-611', 'brand' => 'Nissan',     'model' => 'Frontier', 'year' => 2018, 'type' => VehicleType::Truck->value,      'fuel_type' => FuelType::Diesel->value,   'color' => 'Gris',    'vin' => '1N6DD26S6YC123458', 'engine_number' => 'TD345678', 'mileage' => 112000, 'is_active' => true,  'notes' => null],
            ['plate' => 'DNF-746', 'brand' => 'Hyundai',    'model' => 'Tucson',   'year' => 2022, 'type' => VehicleType::Van->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Negro',   'vin' => 'KM8J33A47NU123459', 'engine_number' => 'G4456789', 'mileage' => 18000,  'is_active' => true,  'notes' => null],
            ['plate' => 'DOU-125', 'brand' => 'Kia',        'model' => 'Sportage', 'year' => 2020, 'type' => VehicleType::Van->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Azul',    'vin' => 'KNDP23A20L7123460', 'engine_number' => 'G4567890', 'mileage' => 54000,  'is_active' => true,  'notes' => null],
            ['plate' => 'DTJ-167', 'brand' => 'Volkswagen', 'model' => 'Amarok',   'year' => 2017, 'type' => VehicleType::Truck->value,      'fuel_type' => FuelType::Diesel->value,   'color' => 'Plata',   'vin' => 'WV1ZZZ2HXHH123461', 'engine_number' => 'CR678901', 'mileage' => 148000, 'is_active' => true,  'notes' => null],
            ['plate' => 'DXB-460', 'brand' => 'Chevrolet',  'model' => 'D-Max',    'year' => 2020, 'type' => VehicleType::Truck->value,      'fuel_type' => FuelType::Diesel->value,   'color' => 'Blanco',  'vin' => '8LBETF1A1L0123462', 'engine_number' => 'C24789012', 'mileage' => 73000,  'is_active' => true,  'notes' => null],
            ['plate' => 'ERQ-000', 'brand' => 'Toyota',     'model' => 'RAV4',     'year' => 2023, 'type' => VehicleType::Van->value,         'fuel_type' => FuelType::Hybrid->value,   'color' => 'Blanco',  'vin' => '2T3P1RFV9PW123463', 'engine_number' => 'A25890123', 'mileage' => 8000,   'is_active' => true,  'notes' => null],
            ['plate' => 'EVU-150', 'brand' => 'Nissan',     'model' => 'X-Trail',  'year' => 2021, 'type' => VehicleType::Van->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Rojo',    'vin' => '5N1AT2MT0LC123464', 'engine_number' => 'MR901234', 'mileage' => 41000,  'is_active' => true,  'notes' => null],
            ['plate' => 'FBZ-947', 'brand' => 'Hyundai',    'model' => 'Santa Fe', 'year' => 2019, 'type' => VehicleType::Van->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Negro',   'vin' => 'KM8SM4HH3KU123465', 'engine_number' => 'G6012345', 'mileage' => 67000,  'is_active' => true,  'notes' => null],
            ['plate' => 'FRX-511', 'brand' => 'Kia',        'model' => 'Seltos',   'year' => 2022, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Gris',    'vin' => 'KNDEU2A20N7123466', 'engine_number' => 'G4123456', 'mileage' => 22000,  'is_active' => true,  'notes' => null],
            ['plate' => 'FTC-582', 'brand' => 'Chevrolet',  'model' => 'Tracker',  'year' => 2021, 'type' => VehicleType::Van->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Azul',    'vin' => '3GNKDCRS5LS123467', 'engine_number' => 'LE234567', 'mileage' => 35000,  'is_active' => true,  'notes' => null],
            ['plate' => 'IIM-294', 'brand' => 'Toyota',     'model' => 'Yaris',    'year' => 2020, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Blanco',  'vin' => 'JTDEPRAE0LJ123468', 'engine_number' => '1NZ345678', 'mileage' => 48000,  'is_active' => true,  'notes' => null],
            ['plate' => 'JOI-552', 'brand' => 'Nissan',     'model' => 'Versa',    'year' => 2023, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Plata',   'vin' => '3N1CN7AP0PL123469', 'engine_number' => 'HR456789', 'mileage' => 5000,   'is_active' => true,  'notes' => null],
            ['plate' => 'KDF-467', 'brand' => 'Volkswagen', 'model' => 'T-Cross',  'year' => 2022, 'type' => VehicleType::Van->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Rojo',    'vin' => 'WV2ZZZE2ZNH123470', 'engine_number' => 'EA567890', 'mileage' => 29000,  'is_active' => true,  'notes' => null],
            ['plate' => 'KVB-507', 'brand' => 'Chevrolet',  'model' => 'Onix',     'year' => 2021, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Negro',   'vin' => '9BWZZZ3BZKP123471', 'engine_number' => 'LUD678901', 'mileage' => 31000,  'is_active' => true,  'notes' => null],
            ['plate' => 'LUV-367', 'brand' => 'Hyundai',    'model' => 'Elantra',  'year' => 2020, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Azul',    'vin' => 'KMHD84LF0LU123472', 'engine_number' => 'G4789012', 'mileage' => 58000,  'is_active' => true,  'notes' => null],
            ['plate' => 'MNB-191', 'brand' => 'Kia',        'model' => 'Rio',      'year' => 2019, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gas->value,      'color' => 'Gris',    'vin' => '3KPA25AB0KE123473', 'engine_number' => 'G4890123', 'mileage' => 76000,  'is_active' => true,  'notes' => 'Convertido a GNV'],
            ['plate' => 'NQZ-252', 'brand' => 'Toyota',     'model' => 'Corolla',  'year' => 2018, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gas->value,      'color' => 'Blanco',  'vin' => '2T1BURHE0JC123474', 'engine_number' => 'ZR901234', 'mileage' => 92000,  'is_active' => true,  'notes' => 'Convertido a GLP'],
            ['plate' => 'ONQ-409', 'brand' => 'Nissan',     'model' => 'Sentra',   'year' => 2022, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Plata',   'vin' => '3N1AB8CV8NY123475', 'engine_number' => 'MR012345', 'mileage' => 14000,  'is_active' => true,  'notes' => null],
            ['plate' => 'OYL-378', 'brand' => 'Volkswagen', 'model' => 'Polo',     'year' => 2021, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Rojo',    'vin' => 'WVWZZZ9NZLM123476', 'engine_number' => 'EA123456', 'mileage' => 26000,  'is_active' => true,  'notes' => null],
            ['plate' => 'PMD-160', 'brand' => 'Kia',        'model' => 'Picanto',  'year' => 2020, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Negro',   'vin' => 'KNADN412XL7123477', 'engine_number' => 'G3234567', 'mileage' => 43000,  'is_active' => true,  'notes' => null],
            ['plate' => 'RML-750', 'brand' => 'Chevrolet',  'model' => 'Sail',     'year' => 2019, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Azul',    'vin' => 'LSVNW218XK7123478', 'engine_number' => 'LHD345678', 'mileage' => 81000,  'is_active' => true,  'notes' => null],
            ['plate' => 'RYB-327', 'brand' => 'Hyundai',    'model' => 'Accent',   'year' => 2023, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Blanco',  'vin' => 'KMHCT41DXPU123479', 'engine_number' => 'G4456789', 'mileage' => 4000,   'is_active' => true,  'notes' => null],
            ['plate' => 'VGP-529', 'brand' => 'Toyota',     'model' => 'Hilux',    'year' => 2016, 'type' => VehicleType::Truck->value,      'fuel_type' => FuelType::Diesel->value,   'color' => 'Gris',    'vin' => 'JT3HP10V0X0123480', 'engine_number' => 'TN567890', 'mileage' => 195000, 'is_active' => true,  'notes' => 'Revisión técnica reciente'],
            ['plate' => 'VPW-746', 'brand' => 'Nissan',     'model' => 'Frontier', 'year' => 2021, 'type' => VehicleType::Truck->value,      'fuel_type' => FuelType::Diesel->value,   'color' => 'Blanco',  'vin' => '1N6DD26S6YC123481', 'engine_number' => 'TD678901', 'mileage' => 37000,  'is_active' => true,  'notes' => null],
            // Inactivos
            ['plate' => 'VQA-643', 'brand' => 'Toyota',     'model' => 'Corolla',  'year' => 2005, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Gris',    'vin' => '2T1BURHE0JC100001', 'engine_number' => 'ZR100001', 'mileage' => 248000, 'is_active' => false, 'notes' => 'Dado de baja por desgaste'],
            ['plate' => 'WRQ-604', 'brand' => 'Nissan',     'model' => 'Sentra',   'year' => 2007, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Rojo',    'vin' => '3N1AB8CV8NY100002', 'engine_number' => 'MR100002', 'mileage' => 232000, 'is_active' => false, 'notes' => 'Accidentado, fuera de servicio'],
            ['plate' => 'WWL-774', 'brand' => 'Hyundai',    'model' => 'Accent',   'year' => 2008, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Azul',    'vin' => 'KMHCT41DXPU100003', 'engine_number' => 'G4100003', 'mileage' => 219000, 'is_active' => false, 'notes' => 'En proceso de remate'],
            ['plate' => 'ZAL-782', 'brand' => 'Volkswagen', 'model' => 'Gol',      'year' => 2009, 'type' => VehicleType::Car->value,         'fuel_type' => FuelType::Gasoline->value, 'color' => 'Blanco',  'vin' => '9BWZZZ377KP100004', 'engine_number' => 'EA100004', 'mileage' => 205000, 'is_active' => false, 'notes' => 'Motor averiado, pendiente reparación'],
        ];

        // Directorio fuente local donde puso las fotos el usuario
        $imagesDir = database_path('seeders/images/vehicles');
        
        $insertData = [];
        foreach ($vehicles as $v) {
            $photoPath = null;
            $plate = $v['plate'];
            $imageFile = $imagesDir . '/' . $plate . '.jpg';
            
            if (File::exists($imageFile)) {
                // Copiar a storage/app/public/vehicles/ con un nombre único o su placa
                $fileName = strtolower($plate) . '.jpg';
                $storedPath = 'vehicles/' . $fileName;
                Storage::disk('public')->put($storedPath, File::get($imageFile));
                $photoPath = $storedPath;
                $this->command?->info("Foto asignada para el vehiculo $plate");
            }
            
            $insertData[] = array_merge($v, [
                'photo_path' => $photoPath,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('vehicles')->insert($insertData);

        $this->command?->info('Módulo Vehículos: 30 registros creados (con fotos locales).');
    }
}
