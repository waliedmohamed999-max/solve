<?php

namespace Tests\Feature;

use App\Support\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSiteContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_content_page_requires_authentication(): void
    {
        $response = $this->get('/admin/site-content');

        $response->assertRedirect('/admin/login');
    }

    public function test_site_content_page_loads(): void
    {
        $response = $this->loginAsAdmin()->get('/admin/site-content');

        $response->assertOk();
    }

    public function test_site_content_update_allows_empty_multiline_fields(): void
    {
        $payload = SiteContent::get();
        $payload['footer']['about_links'] = null;
        $payload['footer']['links'] = '';
        $payload['catalogSections'][0]['items'][0]['features'] = null;

        $response = $this->loginAsAdmin()->post('/admin/site-content', $payload);

        $response->assertRedirect('/admin/site-content');

        $saved = SiteContent::get();

        $this->assertSame([], $saved['footer']['about_links']);
        $this->assertSame([], $saved['footer']['links']);
        $this->assertSame([], $saved['catalogSections'][0]['items'][0]['features']);
    }
}
