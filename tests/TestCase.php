<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        config()->set('admin.username', 'owner@solve.sa');
        config()->set('admin.password', 'SolveOwner@2026');
        config()->set('admin.password_hash', '');
        url()->forceRootUrl('http://localhost');
    }

    protected function loginAsAdmin(): static
    {
        $this->withSession(['admin_authenticated' => true]);

        return $this;
    }
}
