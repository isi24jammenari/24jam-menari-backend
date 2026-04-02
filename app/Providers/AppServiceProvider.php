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
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Storage::extend('google', function ($app, $config) {
            $client = new GoogleClient();
            
            // 1. Tangkap string JSON dari config (hasil dari env Railway)
            $jsonString = $config['credentialsJson'] ?? null;
            
            // 2. Proteksi Lapis 1: Cek apakah variabel kosong
            if (empty($jsonString)) {
                throw new \Exception("SYSTEM HALTED: Variabel GOOGLE_DRIVE_CREDENTIALS_JSON kosong atau tidak terbaca di Railway!");
            }

            // 3. Proteksi Lapis 2: Cek apakah format JSON rusak (typo/kurang kurung) saat di-paste
            $authConfig = json_decode($jsonString, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("SYSTEM HALTED: Format JSON di GOOGLE_DRIVE_CREDENTIALS_JSON rusak! Error: " . json_last_error_msg());
            }

            // 4. Masukkan array kredensial langsung ke Google Client (Tanpa file fisik)
            $client->setAuthConfig($authConfig);
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