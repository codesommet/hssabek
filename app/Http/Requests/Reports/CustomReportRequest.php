<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class CustomReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'    => ['required', 'string', 'max:255'],
            'content'  => ['required', 'string', 'max:500000'],
            'category' => ['nullable', 'string', 'max:100'],
            'status'   => ['required', 'in:draft,published'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'Le titre est obligatoire.',
            'title.max'        => 'Le titre ne doit pas dépasser 255 caractères.',
            'content.required' => 'Le contenu du rapport est obligatoire.',
            'content.max'      => 'Le contenu est trop volumineux.',
            'category.max'     => 'La catégorie ne doit pas dépasser 100 caractères.',
            'status.required'  => 'Le statut est obligatoire.',
            'status.in'        => 'Le statut doit être « Brouillon » ou « Publié ».',
        ];
    }
}
