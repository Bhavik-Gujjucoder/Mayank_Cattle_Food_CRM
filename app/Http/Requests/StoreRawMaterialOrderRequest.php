<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use App\Support\RawMaterialOrderPriceBasis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRawMaterialOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_unique_id'     => 'required|string|max:30',
            'supplier_broker_id'  => 'required|exists:supplier_brokers,id',
            'supplier_id'         => 'required|exists:suppliers,id',
            'supplier_order_id'   => 'nullable|string|max:100',
            'order_date'          => 'required|date|before_or_equal:today',
            'price_basis'         => ['required', Rule::in(RawMaterialOrderPriceBasis::options())],
            'raw_material_id'     => 'required|array|min:1',
            'raw_material_id.*'   => 'required|exists:raw_materials,id',
            'total_qty'           => 'required|array|min:1',
            'total_qty.*'         => 'required|integer|min:1',
            'price'               => 'required|array|min:1',
            'price.*'             => 'required|numeric|gt:0',
            'other_expense'       => 'required|array|min:1',
            'other_expense.*'     => 'nullable|numeric|min:0',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $supplier = Supplier::query()->find($this->input('supplier_id'));
            if (! $supplier || (int) $supplier->supplier_broker_id !== (int) $this->input('supplier_broker_id')) {
                $validator->errors()->add('supplier_id', 'Selected supplier does not belong to the chosen supplier broker.');
            }
        });
    }
}
