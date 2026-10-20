<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AskAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'question' => ['nullable', 'required_without:photo', 'string', 'max:1000'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:10240'],
            'conversation_id' => ['nullable', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'question.required_without' => 'Напишіть запитання або додайте фото лічильника.',
            'question.max' => 'Запитання не може бути довшим за 1000 символів.',
            'photo.file' => 'Не вдалося завантажити фото.',
            'photo.mimes' => 'Фото має бути у форматі JPG, PNG, WebP або HEIC.',
            'photo.max' => 'Фото завелике. Максимальний розмір — 10 МБ.',
            'photo.uploaded' => 'Не вдалося завантажити фото. Спробуйте менший файл.',
        ];
    }
}
