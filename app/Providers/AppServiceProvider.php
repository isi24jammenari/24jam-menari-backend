<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
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
                // SANGAT KRUSIAL: Ini yang membuat folderId Anda diakui sebagai Root
                $options['folderId'] = $config['folderId']; 
            }

            // 1. Inisialisasi Google Client
            $client = new \Google\Client();
            $client->setScopes([\Google\Service\Drive::DRIVE]);
            
            // 2. Baca kredensial dari JSON string yang sudah kita buat one-liner
            $client->setAuthConfig(json_decode($config['credentialsJson'], true));
            
            // 3. Matikan defer untuk memastikan koneksi langsung dieksekusi
            $client->setDefer(false);

            // 4. Inisialisasi layanan Google Drive
            $service = new \Google\Service\Drive($client);

            // 5. Binding adapter masbug dengan konfigurasi yang benar
            $adapter = new GoogleDriveAdapter($service, $config['folderId'] ?? '', $options);
            $driver = new Filesystem($adapter);

            return $driver;
        });
    }
}