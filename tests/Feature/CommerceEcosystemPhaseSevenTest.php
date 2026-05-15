<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceEcosystemPhaseSevenTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_seven_ecosystem_pages_load(): void
    {
        $pages = [
            '/admin/commerce-infrastructure' => 'Commerce Infrastructure',
            '/admin/headless-commerce' => 'Headless Commerce',
            '/admin/website-builder' => 'Website Builder',
            '/admin/app-ecosystem' => 'App Marketplace',
            '/admin/b2b-commerce' => 'B2B Commerce',
            '/admin/subscription-commerce' => 'Subscription Commerce',
            '/admin/omnichannel' => 'Omnichannel Commerce',
            '/admin/ai-suite' => 'AI Commerce Suite',
            '/admin/enterprise-operations' => 'Enterprise Operations',
            '/admin/devops-scalability' => 'DevOps &amp; Scalability',
            '/admin/advanced-ux' => 'Advanced User Experience',
            '/admin/business-growth' => 'Business Growth System',
            '/admin/white-label' => 'White Label System',
            '/admin/global-admin' => 'Global Admin Center',
            '/admin/final-polish' => 'Final Polish',
        ];

        foreach ($pages as $path => $text) {
            $this->loginAsAdmin()->get($path)
                ->assertOk()
                ->assertSee($text, false);
        }
    }

    public function test_headless_commerce_page_contains_api_surface(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/headless-commerce');

        $response->assertOk();
        $response->assertSee('Storefront API');
        $response->assertSee('GraphQL');
        $response->assertSee('Webhooks');
    }

    public function test_website_builder_and_white_label_pages_show_customization_controls(): void
    {
        $this->loginAsAdmin()->get('/admin/website-builder')
            ->assertOk()
            ->assertSee('Page Builder')
            ->assertSee('Theme Marketplace')
            ->assertSee('Live Preview');

        $this->loginAsAdmin()->get('/admin/white-label')
            ->assertOk()
            ->assertSee('Custom Domains')
            ->assertSee('System Emails')
            ->assertSee('Brand Profiles');
    }

    public function test_global_admin_and_final_polish_pages_show_launch_controls(): void
    {
        $this->loginAsAdmin()->get('/admin/global-admin')
            ->assertOk()
            ->assertSee('Platform GMV')
            ->assertSee('AI Services');

        $this->loginAsAdmin()->get('/admin/final-polish')
            ->assertOk()
            ->assertSee('Design System')
            ->assertSee('Security Review')
            ->assertSee('Documentation');
    }
}
