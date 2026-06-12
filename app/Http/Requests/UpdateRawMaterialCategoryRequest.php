<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateRawMaterialCategoryRequest extends StoreRawMaterialCategoryRequest
{
    public function rules(): array
    {
        $categoryId = $this->route('raw_material_category')?->id ?? $this->route('category');

        return [
            'name'   => [
                'required',
                'string',
                'max:255',
                Rule::unique('raw_material_categories', 'name')->ignore($categoryId),
            ],
            'status' => 'required|in:0,1',
        ];
    }
}
