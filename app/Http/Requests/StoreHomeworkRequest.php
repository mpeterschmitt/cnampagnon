<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHomeworkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'due_date' => ['required', 'date', 'after:now'],
            'priority' => ['required', 'in:low,medium,high'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le titre du devoir est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'due_date.required' => 'La date de rendu est obligatoire.',
            'due_date.after' => 'La date de rendu doit être dans le futur.',
            'priority.required' => 'La priorité est obligatoire.',
            'priority.in' => 'La priorité doit être low, medium ou high.',
            'end_time.after' => 'La date de fin doit être après la date de début.',
            'color.regex' => 'La couleur doit être au format hexadécimal (#000000).',
        ];
    }
}
