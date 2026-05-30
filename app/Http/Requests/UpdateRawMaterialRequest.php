<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRawMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('raw_material')?->id;

        return [
            'name'   => 'required|string|max:255|unique:raw_materials,name,' . $id . ',id,deleted_at,NULL',
            'unit'   => 'required|string|max:50',
            'status' => 'required|in:0,1',
        ];
    }
}
