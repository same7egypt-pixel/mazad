<?php

namespace Tests\Feature;

use Tests\TestCase;

class RenderStaticFrontendBlueprintTest extends TestCase
{
    public function test_blueprint_defines_a_render_static_site_for_the_public_marketplace(): void
    {
        $blueprint = file_get_contents(dirname(base_path()).'/render.yaml');

        $this->assertIsString($blueprint);
        $this->assertStringContainsString('name: mazad-marketplace-web', $blueprint);
        $this->assertStringContainsString('runtime: static', $blueprint);
        $this->assertStringContainsString('staticPublishPath: ./dist/public', $blueprint);
        $this->assertStringContainsString('source: /*', $blueprint);
        $this->assertStringContainsString('destination: /index.html', $blueprint);
        $this->assertStringContainsString('https://mazad-marketplace-web.onrender.com', $blueprint);
    }
}
