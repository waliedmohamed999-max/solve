<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Support\PlatformBackup;
use App\Support\SubscriptionLifecycle;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('solve:admin-hash {password}', function (string $password) {
    if (mb_strlen($password) < 12) {
        $this->error('Admin password must be at least 12 characters.');

        return Command::FAILURE;
    }

    $this->line(Hash::make($password));

    return Command::SUCCESS;
})->purpose('Generate a secure ADMIN_PASSWORD_HASH for production deployments');

Artisan::command('solve:backup {--label=scheduled}', function () {
    $backup = PlatformBackup::create((string) $this->option('label'));

    $this->line('Backup created: ' . $backup['path']);
    $this->line('Checksum: ' . $backup['checksum']);

    return Command::SUCCESS;
})->purpose('Create a JSON backup of the core Solve SaaS operational tables');

Artisan::command('solve:subscriptions:enforce', function () {
    $result = SubscriptionLifecycle::enforceExpirations();

    $this->line('Processed stores: ' . $result['processed']);
    $this->line('Suspended stores: ' . $result['suspended']);
    $this->line('Expiry warnings: ' . $result['warnings']);

    return Command::SUCCESS;
})->purpose('Enforce subscription expiry warnings and automatic store suspension');
