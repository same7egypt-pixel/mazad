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
    }
}
