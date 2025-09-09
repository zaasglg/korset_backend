<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckPhoneNumberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Публичный endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\s\-\(\)]+$/' // Разрешаем цифры, пробелы, дефисы, скобки и плюс
            ]
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'phone_number.required' => 'Номер телефона обязателен для заполнения',
            'phone_number.string' => 'Номер телефона должен быть строкой',
            'phone_number.max' => 'Номер телефона не должен превышать 20 символов',
            'phone_number.regex' => 'Неверный формат номера телефона',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Нормализуем номер телефона перед валидацией
        if ($this->has('phone_number')) {
            $phoneNumber = $this->input('phone_number');
            // Убираем лишние пробелы в начале и конце
            $phoneNumber = trim($phoneNumber);
            
            $this->merge([
                'phone_number' => $phoneNumber
            ]);
        }
    }
}
