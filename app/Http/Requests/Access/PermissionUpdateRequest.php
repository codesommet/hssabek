<?php

namespace App\Http\Requests\Access;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only SuperAdmin can update permissions
        return $this->user()->tenant_id === null;
    }

    public function rules(): array
    {
        $permission = $this->route('permission');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z_]+\.[a-z_]+\.[a-z_]+$/',
                Rule::unique('permissions')->where(function ($query) {
                    $query->where('guard_name', 'web')
                        ->whereNull('tenant_id');
                })->ignore($permission->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Permission name must follow the format: group.module.action (e.g., sales.invoices.create)',
            'name.unique' => 'This permission already exists.',
        ];
    }
}
