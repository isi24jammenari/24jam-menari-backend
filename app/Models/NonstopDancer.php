<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonstopDancer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'masterpiece_title',
        'companions_identity',
        'health_cert_file_id',
        'cv_file_id',
        'photo_file_id',
        'video_file_id',
    ];
}