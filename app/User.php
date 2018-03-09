<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\App;

class User extends Authenticatable
{
    use Notifiable;

    const ROLE_ADMIN = 'admin';
    const ROLE_OWNER = 'owner';
    const ROLE_WORKER = 'worker';
    const ROLE_CLIENT = 'client';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'phone', 'password', 'verify_code', 'role'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    protected $perPage = 25;

    public function device_tokens()
    {
        return $this->hasMany('App\DeviceToken');
    }

    public function refreshDeviceToken($token, $options = []){
        $platform = array_get($options, 'platform');
        $location = array_get($options, 'location_id', 1);

        $device_token = DeviceToken::where('token', $token)->first();
        if(!$location){
            $location = $this->location_id;
        }
        if($device_token){
            if($device_token->user_id != $this->id){
                $device_token->user_id = $this->id;

            }
            $device_token->location_id = $location;
            $device_token->locale = App::getLocale();
            $device_token->save();

        }else{

            $type = DeviceToken::TYPE_FCM;
            if($platform == 'ios') {
                $type = DeviceToken::TYPE_APN;
            }

            DeviceToken::create([
                'user_id' => $this->id,
                'type' => $type,
                'token' => $token,
                'location_id' => $location,
                'locale' => App::getLocale()
            ]);
        }
        return true;
    }

    public function routeNotificationForApn()
    {
        return $this->device_tokens()->where('type', DeviceToken::TYPE_APN)->pluck('token')->toArray();
    }

    public static function getRoles(){
        return [
            self::ROLE_CLIENT,
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
            self::ROLE_WORKER,
        ];
    }
}
