<?php

namespace App\Models;

use App\Models\StateManagement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CityManagement extends Model
{
    use SoftDeletes;

    protected $table = 'city_management';
    protected $guarded = [];

    public function statusBadge()
    {
        return $this->status == 1 ? '<span class="badge badge-pill badge-status bg-success">Active</span>' : '<span class="badge badge-pill badge-status bg-danger">Inactive</span>';
    }

    public function state()
    {
        return $this->hasOne(StateManagement::class, 'id','state_id');
    }



}
