<?php

namespace App;

use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class MenuItemOption extends Model
{
    use Translatable;

    public $timestamps = false;

    protected $fillable = [
        'menu_item_id', 'name', 'count', 'price'
    ];

    public $translatedAttributes = ['name'];

    public function menu_item()
    {
        return $this->belongsTo('App\MenuItem');
    }
}
