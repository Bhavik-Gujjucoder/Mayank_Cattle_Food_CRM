<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRawMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'   => 'required|string|max:255|unique:raw_materials,name,NULL,id,deleted_at,NULL',
            'unit'   => 'required|string|max:50',
            'status' => 'required|in:0,1',
        ];
    }
}
