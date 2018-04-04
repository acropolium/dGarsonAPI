<?php
namespace App;

use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use Translatable;

    public $timestamps = false;
    public $translatedAttributes = ['name', 'address'];

    const CURRENCY_UAH = 'UAH';
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_USD = 'USD';

    public function locations()
    {
        return $this->hasMany('App\Location');
    }

    public function orders()
    {
        return $this->hasMany('App\Order');
    }

    public function getLogoAttribute($value)
    {
        return $value ? asset('storage/' . $value) : $value;
    }

    public function latestOrder()
    {
        return $this->hasOne('App\Order')->latest();
    }

    public static function getAvailableCurrencies()
    {
        return [self::CURRENCY_USD, self::CURRENCY_EUR, self::CURRENCY_UAH];
    }
}
