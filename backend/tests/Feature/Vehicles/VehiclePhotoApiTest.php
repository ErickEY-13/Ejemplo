<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VehiclePhotoApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El contenedor de PHP no tiene la extensión GD, que es la que usa
     * `UploadedFile::fake()->image()` para generar una imagen real. En su
     * lugar se arma un PNG mínimo válido a mano (encabezado real, así
     * `getimagesize()` lo reconoce) y se rellena con ceros para simular
     * distintos tamaños.
     */
    private function fakeImage(string $name, int $extraBytes = 0): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        );

        return UploadedFile::fake()->createWithContent($name, $png.str_repeat("\0", $extraBytes));
    }

    #[Test]
    public function sube_una_foto_a_un_vehiculo_sin_foto(): void
    {
        Storage::fake('public');
        $vehicle = Vehicle::factory()->create();

        $this->postJson("/api/vehicles/{$vehicle->id}/photo", [
            'photo' => $this->fakeImage('auto.png'),
        ])
            ->assertOk()
            ->assertJsonPath('data.photo_url', fn (?string $url) => $url !== null);

        Storage::disk('public')->assertExists($vehicle->fresh()->photo_path);
    }

    #[Test]
    public function reemplaza_la_foto_existente_y_borra_la_anterior(): void
    {
        Storage::fake('public');
        $vehicle = Vehicle::factory()->create();

        $this->postJson("/api/vehicles/{$vehicle->id}/photo", [
            'photo' => $this->fakeImage('primera.png'),
        ])->assertOk();

        $primeraRuta = $vehicle->fresh()->photo_path;

        $this->postJson("/api/vehicles/{$vehicle->id}/photo", [
            'photo' => $this->fakeImage('segunda.png'),
        ])->assertOk();

        $segundaRuta = $vehicle->fresh()->photo_path;

        Storage::disk('public')->assertMissing($primeraRuta);
        Storage::disk('public')->assertExists($segundaRuta);
    }

    #[Test]
    public function rechaza_un_archivo_que_no_es_imagen(): void
    {
        Storage::fake('public');
        $vehicle = Vehicle::factory()->create();

        $this->postJson("/api/vehicles/{$vehicle->id}/photo", [
            'photo' => UploadedFile::fake()->create('documento.pdf', 100),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    }

    #[Test]
    public function rechaza_una_imagen_demasiado_pesada(): void
    {
        Storage::fake('public');
        $vehicle = Vehicle::factory()->create();

        $this->postJson("/api/vehicles/{$vehicle->id}/photo", [
            'photo' => $this->fakeImage('grande.png', 4200 * 1024),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    }

    #[Test]
    public function elimina_la_foto_de_un_vehiculo(): void
    {
        Storage::fake('public');
        $vehicle = Vehicle::factory()->create();

        $this->postJson("/api/vehicles/{$vehicle->id}/photo", [
            'photo' => $this->fakeImage('auto.png'),
        ])->assertOk();

        $ruta = $vehicle->fresh()->photo_path;

        $this->deleteJson("/api/vehicles/{$vehicle->id}/photo")
            ->assertOk()
            ->assertJsonPath('data.photo_url', null);

        Storage::disk('public')->assertMissing($ruta);
        $this->assertNull($vehicle->fresh()->photo_path);
    }

    #[Test]
    public function eliminar_la_foto_de_un_vehiculo_sin_foto_no_falla(): void
    {
        Storage::fake('public');
        $vehicle = Vehicle::factory()->create();

        $this->deleteJson("/api/vehicles/{$vehicle->id}/photo")
            ->assertOk()
            ->assertJsonPath('data.photo_url', null);
    }
}
