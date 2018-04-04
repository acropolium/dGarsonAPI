<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    const TYPE_FCM = 'fcm';
    const TYPE_APN = 'apn';

    protected $fillable = ['user_id', 'type', 'token', 'locale', 'location_id'];
}
