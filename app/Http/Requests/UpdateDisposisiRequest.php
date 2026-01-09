<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDisposisiRequest extends FormRequest
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
            'diteruskan_ke_id' => 'required|exists:users,id',
            'instruksi' => 'required|string|max:1000',
            'batas_waktu' => 'required|date|after:today',
            'catatan' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'diteruskan_ke_id.required' => 'Penerima disposisi harus dipilih',
            'diteruskan_ke_id.exists' => 'User penerima tidak valid',
            'instruksi.required' => 'Instruksi harus diisi',
            'instruksi.max' => 'Instruksi tidak boleh lebih dari 1000 karakter',
            'batas_waktu.required' => 'Batas waktu harus diisi',
            'batas_waktu.after' => 'Batas waktu harus lebih dari hari ini',
            'catatan.max' => 'Catatan tidak boleh lebih dari 500 karakter',
        ];
    }
}
