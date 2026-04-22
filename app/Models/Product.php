<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $table = 'products';
    protected $guarded = [];


    public function statusBadge()
    {
        return $this->status == 1
            ? '<span class="badge badge-pill badge-success">Active</span>'
            : '<span class="badge badge-pill badge-danger">Inactive</span>';
    }

}
