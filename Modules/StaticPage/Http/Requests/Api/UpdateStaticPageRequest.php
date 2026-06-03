<?php

namespace Modules\StaticPage\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaticPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => 'nullable|string|max:255|unique:static_pages,slug,' . $this->route('id'),
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'content_ar' => 'nullable|string',
            'content_en' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'المعرف الفريد مستخدم بالفعل',
        ];
    }
}
