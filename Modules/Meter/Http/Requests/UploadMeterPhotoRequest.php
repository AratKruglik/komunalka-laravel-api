<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMeterPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'photo.required' => 'Фото є обов\'язковим.',
            'photo.image' => 'Файл має бути зображенням.',
            'photo.max' => 'Розмір фото не може перевищувати 10 МБ.',
        ];
    }
}
