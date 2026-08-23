<?php

namespace Tests\Feature;

use Tests\TestCase;

class NeonDatabaseSwitchConfigurationTest extends TestCase
{
    public function test_postgres_connection_uses_neon_only_when_explicitly_enabled(): void
    {
        $configuration = file_get_contents(base_path('config/database.php'));

        $this->assertIsString($configuration);
        $this->assertStringContainsString("env('USE_NEON_DATABASE', false) ? env('NEON_DATABASE_URL') : env('DB_URL')", $configuration);
    }
}
