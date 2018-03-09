<?php

namespace App;

use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use Translatable;

    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'location_id', 'name', 'description', 'volume', 'price', 'logo'
    ];

    public $translatedAttributes = ['name', 'description'];

    public function company()
    {
        return $this->belongsTo('App\Company');
    }

    /**
     * Get the options for the Menu item.
     */
    public function options()
    {
        return $this->hasMany('App\MenuItemOption');
    }

    public function getLogoAttribute($value)
    {
        return $value ? asset('storage/'.$value) : $value;
    }
}
