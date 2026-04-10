<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter; // <-- INI KUNCI UTAMANYA
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Daftarkan driver custom 'google' secara eksplisit
        Storage::extend('google', function ($app, $config) {
            $options = [];

            if (!empty($config['folderId'])) {
                $options['folderId'] = $config['folderId']; 
            }

            // 1. Inisialisasi Google Client
            $client = new \Google\Client();
            $client->setScopes([\Google\Service\Drive::DRIVE]);
            $client->setAuthConfig(json_decode($config['credentialsJson'], true));
            $client->setDefer(false);

            // 2. Inisialisasi layanan Google Drive
            $service = new \Google\Service\Drive($client);

            // 3. Binding adapter masbug
            $adapter = new GoogleDriveAdapter($service, $config['folderId'] ?? '', $options);
            $driver = new Filesystem($adapter);

            // 4. BUNGKUS dengan FilesystemAdapter Laravel agar put() dan storeAs() terbaca!
            return new FilesystemAdapter($driver, $adapter, $config);
        });
    }
}