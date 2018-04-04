<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    const STATE_PENDING = 'pending';
    const STATE_RECIEVED = 'recieved';
    const STATE_INPROGRESS = 'inprogress';
    const STATE_READY = 'ready';
    const STATE_NOTPICKED = 'notpicked';
    const STATE_PAYED = 'payed';
    const STATE_CANCEL = 'cancel';

    protected $casts = ['items' => 'array'];

    protected $appends = ['take_away_time'];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('c');
    }

    public function getTakeAwayTimeAttribute()
    {
        return $this->created_at->addMinutes($this->desired_time)->format('c');
    }

    public function company()
    {
        return $this->belongsTo('App\Company');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('state', [
            self::STATE_CANCEL,
            self::STATE_PAYED
        ]);
    }

    public static function getStates()
    {
        return [
            self::STATE_PENDING,
            self::STATE_RECIEVED,
            self::STATE_INPROGRESS,
            self::STATE_READY,
            self::STATE_NOTPICKED,
            self::STATE_PAYED,
            self::STATE_CANCEL
        ];
    }

    public static function checkType($type)
    {
        if (!$type) {
            return false;
        }
        return array_search($type, self::getStates()) !== false;
    }
}
