<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HealthTest extends TestCase
{
    #[Test]
    public function el_endpoint_de_salud_responde(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'app', 'environment', 'database', 'timestamp']);
    }

    #[Test]
    public function expone_el_catalogo_de_modulos(): void
    {
        $this->getJson('/api/modules')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['key', 'name', 'description', 'path', 'api']]]);
    }

    #[Test]
    public function la_raiz_identifica_la_api(): void
    {
        $this->getJson('/')
            ->assertOk()
            ->assertJsonPath('type', 'api');
    }
}
