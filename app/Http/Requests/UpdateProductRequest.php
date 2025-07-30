<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|exists:categories,id',
            'city_id' => 'sometimes|exists:cities,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:5000',
            'main_photo' => 'sometimes|string',
            'video' => 'nullable|file|mimes:mp4,avi,mov,wmv,flv,webm,mkv|max:102400', // 100MB max
            'price' => 'sometimes|numeric|min:0',
            'address' => 'sometimes|string|max:500',
            'whatsapp_number' => 'nullable|string|max:20',
            'phone_number' => 'nullable|string|max:20',
            'is_video_call_available' => 'sometimes|boolean',
            'ready_for_video_demo' => 'sometimes|boolean',
            'expires_at' => 'sometimes|date|after:now',
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'sold'])]
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'Выбранная категория не существует',
            'city_id.exists' => 'Выбранный город не существует',
            'name.max' => 'Название товара не должно превышать 255 символов',
            'description.max' => 'Описание товара не должно превышать 5000 символов',
            'video.mimes' => 'Видео должно быть в формате: mp4, avi, mov, wmv, flv, webm, mkv',
            'video.max' => 'Размер видео не должен превышать 100MB',
            'price.numeric' => 'Цена должна быть числом',
            'price.min' => 'Цена должна быть больше или равна 0',
            'address.max' => 'Адрес не должен превышать 500 символов',
            'whatsapp_number.max' => 'Номер WhatsApp не должен превышать 20 символов',
            'phone_number.max' => 'Номер телефона не должен превышать 20 символов',
            'expires_at.date' => 'Дата окончания должна быть корректной датой',
            'expires_at.after' => 'Дата окончания должна быть в будущем',
            'status.in' => 'Статус должен быть одним из: активный, неактивный, продан'
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
