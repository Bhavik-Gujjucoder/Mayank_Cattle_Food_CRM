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
            'name'                     => 'required|string|max:255|unique:raw_materials,name,NULL,id,deleted_at,NULL',
            'raw_material_category_id' => 'required|exists:raw_material_categories,id',
            'unit'                     => 'required|string|max:50',
            'status'                   => 'required|in:0,1',
        ];
    }
}
