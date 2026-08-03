<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $sku = $this->input('sku');

        $this->merge([
            'sku' => is_string($sku)
                ? Str::upper(trim($sku))
                : $sku,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->where(
                    fn (Builder $query) => $query->where(
                        'user_id',
                        $this->user()->id,
                    ),
                ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'price' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:99999999.99',
            ],

            'stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Podaj nazwę produktu.',
            'name.max' => 'Nazwa produktu może mieć maksymalnie 255 znaków.',

            'sku.required' => 'Podaj kod SKU produktu.',
            'sku.max' => 'Kod SKU może mieć maksymalnie 100 znaków.',
            'sku.unique' => 'Produkt z takim SKU już istnieje w Twoim katalogu.',

            'description.max' => 'Opis może mieć maksymalnie 5000 znaków.',

            'price.required' => 'Podaj cenę produktu.',
            'price.numeric' => 'Cena musi być liczbą.',
            'price.decimal' => 'Cena może mieć maksymalnie dwa miejsca po przecinku.',
            'price.min' => 'Cena nie może być ujemna.',
            'price.max' => 'Podana cena jest zbyt wysoka.',

            'stock.required' => 'Podaj stan magazynowy.',
            'stock.integer' => 'Stan magazynowy musi być liczbą całkowitą.',
            'stock.min' => 'Stan magazynowy nie może być ujemny.',

            'is_active.required' => 'Określ status produktu.',
            'is_active.boolean' => 'Status produktu ma nieprawidłową wartość.',
        ];
    }
}