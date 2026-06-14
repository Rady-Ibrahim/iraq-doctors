<?php

namespace Modules\StaticPage\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class CreateStaticPageRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => 'required|string|max:255|unique:static_pages,slug',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'content_ar' => 'required|string',
            'content_en' => 'required|string',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'المعرف الفريد مطلوب',
            'slug.unique' => 'المعرف الفريد مستخدم بالفعل',
            'title_ar.required' => 'العنوان بالعربية مطلوب',
            'title_en.required' => 'العنوان بالإنجليزية مطلوب',
            'content_ar.required' => 'المحتوى بالعربية مطلوب',
            'content_en.required' => 'المحتوى بالإنجليزية مطلوب',
        ];
    }
}
