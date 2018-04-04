<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    public $timestamps = false;

    protected $fillable = ['company_id', 'address', 'phone', 'lat', 'lng'];

    public function company()
    {
        return $this->belongsTo('App\Company');
    }
}
