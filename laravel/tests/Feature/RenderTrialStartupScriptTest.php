<?php

namespace Tests\Feature;

use Tests\TestCase;

class RenderTrialStartupScriptTest extends TestCase
{
    public function test_the_trial_startup_script_seeds_rbac_before_provisioning_the_first_admin(): void
    {
        $script = file_get_contents(base_path('docker/render/start-trial-web.sh'));

        $this->assertIsString($script);

        $rolesSeedOffset = strpos($script, 'RolePermissionSeeder');
        $adminProvisionOffset = strpos($script, 'marketplace:provision-first-admin');

        $this->assertNotFalse($rolesSeedOffset);
        $this->assertNotFalse($adminProvisionOffset);
        $this->assertLessThan($adminProvisionOffset, $rolesSeedOffset);
        $this->assertStringContainsString("--class='Database\\Seeders\\MarketplaceReferenceSeeder'", $script);
    }

    public function test_neon_migration_is_explicitly_gated_and_requires_an_empty_direct_target(): void
    {
        $script = file_get_contents(base_path('docker/render/migrate-to-neon.sh'));

        $this->assertIsString($script);
        $this->assertStringContainsString('NEON_MIGRATION_ENABLED:-false', $script);
        $this->assertStringContainsString('NEON_DATABASE_URL', $script);
        $this->assertStringContainsString('*-pooler*', $script);
        $this->assertStringContainsString('target must be an empty public schema', $script);
        $this->assertStringContainsString('pg_dump', $script);
        $this->assertStringContainsString('pg_restore', $script);
        $this->assertStringContainsString("sed '/ SCHEMA - public /d", $script);
        $this->assertStringContainsString('--use-list="${restore_list_file}"', $script);
    }

    public function test_render_image_uses_the_matching_postgres_client_library_for_neon_migration(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile.render'));

        $this->assertIsString($dockerfile);
        $this->assertStringContainsString('libpq.so.5.18', $dockerfile);
        $this->assertStringContainsString('postgres18-client.conf', $dockerfile);
        $this->assertStringContainsString('ldconfig', $dockerfile);
    }
}
