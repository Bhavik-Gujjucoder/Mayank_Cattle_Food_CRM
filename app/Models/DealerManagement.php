<?php

namespace App\Models;

use App\Models\CityManagement;
use App\Models\StateManagement;
use Illuminate\Database\Eloquent\Model;

class DealerManagement extends Model
{
    protected $table = 'dealer_management';
    protected $guarded = [];

    /**
     * Broker (User with broker role)
     */
    public function broker()
    {
        return $this->belongsTo(User::class, 'broker_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function city()
    {
        return $this->hasOne(CityManagement::class, 'id', 'city_id');
    }
    public function state()
    {
        return $this->hasOne(StateManagement::class, 'id', 'state_id');
    }

}
