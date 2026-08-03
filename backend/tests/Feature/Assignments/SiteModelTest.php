<?php

declare(strict_types=1);

namespace Tests\Feature\Assignments;

use App\Modules\Assignments\Database\Seeders\SitesSeeder;
use App\Modules\Assignments\Models\Site;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function crea_una_sede_con_codigo_unico(): void
    {
        Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $this->assertDatabaseHas('sites', ['code' => 'main', 'name' => 'Sede Central']);
    }

    #[Test]
    public function no_permite_dos_sedes_con_el_mismo_codigo(): void
    {
        Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $this->expectException(QueryException::class);

        Site::query()->create(['code' => 'main', 'name' => 'Otra sede']);
    }

    #[Test]
    public function el_seeder_crea_las_seis_sedes_iniciales(): void
    {
        $this->seed(SitesSeeder::class);

        $this->assertDatabaseCount('sites', 6);
        $this->assertDatabaseHas('sites', ['code' => 'main']);
        $this->assertDatabaseHas('sites', ['code' => 'annex']);
    }
}
