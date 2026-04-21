<?php

namespace App\Models;

use App\Models\CityManagement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StateManagement extends Model
{
    use SoftDeletes;
    protected $table = 'state_management';
    protected $guarded = [];

    public function statusBadge()
    {
        return $this->status == 1 ? '<span class="badge badge-pill badge-status bg-success">Active</span>' : '<span class="badge badge-pill badge-status bg-danger">Inactive</span>';
    }

    public function cities()
    {
        return $this->hasMany(CityManagement::class, 'state_id')->where('status',1);
    }

}
