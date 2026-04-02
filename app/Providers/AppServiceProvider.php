<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDriveService;
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
        // MERAKIT JEMBATAN: Mengenalkan driver 'google' ke dalam ekosistem Laravel
        Storage::extend('google', function ($app, $config) {
            $client = new GoogleClient();
            
            // PROTEKSI CLOUD STATELESS: 
            // Kita prioritaskan membaca kredensial langsung dari Environment Variable Railway.
            // Jika tidak ada (misal di lokal), baru fallback membaca file fisik json.
            $envJson = env('GOOGLE_DRIVE_CREDENTIALS_JSON');
            
            if (!empty($envJson)) {
                $client->setAuthConfig(json_decode($envJson, true));
            } else {
                $client->setAuthConfig($config['serviceAccount']);
            }
            
            $client->addScope(GoogleDriveService::DRIVE);
            
            $service = new GoogleDriveService($client);
            $folderId = $config['folderId'] ?? '/';
            $options = [];
            
            $adapter = new GoogleDriveAdapter($service, $folderId, $options);
            $driver = new Filesystem($adapter);
            
            return new FilesystemAdapter($driver, $adapter, $config);
        });
    }
}