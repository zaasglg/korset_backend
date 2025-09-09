<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'publication_price_id' => 'required|exists:publication_prices,id',
            'category_id' => 'required|exists:categories,id',
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'video' => 'nullable|file|mimes:mp4,avi,mov,wmv,flv,webm,mkv|max:102400', // 100MB max
            'price' => 'required|numeric|min:0',
            'address' => 'required|string|max:500',
            'whatsapp_number' => 'nullable|string|max:20',
            'phone_number' => 'nullable|string|max:20',
            'is_video_call_available' => 'boolean',
            'ready_for_video_demo' => 'boolean',
            'parameters' => 'nullable|array',
            'parameters.*.parameter_id' => 'required_with:parameters|exists:product_parameters,id',
            'parameters.*.value' => 'required_with:parameters|string|max:1000'
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'publication_price_id.required' => 'Тариф публикации обязателен для заполнения',
            'publication_price_id.exists' => 'Выбранный тариф не существует',
            'category_id.required' => 'Категория обязательна для заполнения',
            'category_id.exists' => 'Выбранная категория не существует',
            'city_id.required' => 'Город обязателен для заполнения',
            'city_id.exists' => 'Выбранный город не существует',
            'name.required' => 'Название товара обязательно для заполнения',
            'name.max' => 'Название товара не должно превышать 255 символов',
            'description.required' => 'Описание товара обязательно для заполнения',
            'description.max' => 'Описание товара не должно превышать 5000 символов',
            'video.mimes' => 'Видео должно быть в формате: mp4, avi, mov, wmv, flv, webm, mkv',
            'video.max' => 'Размер видео не должен превышать 100MB',
            'price.required' => 'Цена обязательна для заполнения',
            'price.numeric' => 'Цена должна быть числом',
            'price.min' => 'Цена должна быть больше или равна 0',
            'address.required' => 'Адрес обязателен для заполнения',
            'address.max' => 'Адрес не должен превышать 500 символов',
            'whatsapp_number.max' => 'Номер WhatsApp не должен превышать 20 символов',
            'phone_number.max' => 'Номер телефона не должен превышать 20 символов',
            'parameters.*.parameter_id.required_with' => 'ID параметра обязателен',
            'parameters.*.parameter_id.exists' => 'Выбранный параметр не существует',
            'parameters.*.value.required_with' => 'Значение параметра обязательно',
            'parameters.*.value.max' => 'Значение параметра не должно превышать 1000 символов'
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        $updates = [];
        
        // Convert string boolean values
        if ($this->input('is_video_call_available') !== null) {
            $updates['is_video_call_available'] = filter_var($this->input('is_video_call_available'), FILTER_VALIDATE_BOOLEAN);
        }
        
        if ($this->input('ready_for_video_demo') !== null) {
            $updates['ready_for_video_demo'] = filter_var($this->input('ready_for_video_demo'), FILTER_VALIDATE_BOOLEAN);
        }
        
        if (!empty($updates)) {
            $this->merge($updates);
        }
    }
}
