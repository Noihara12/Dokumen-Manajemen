<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSuratKeluarRequest extends FormRequest
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
            'tanggal_surat' => 'required|date',
            'tujuan' => 'required|string|max:255',
            'perihal' => 'required|string|max:500',
            'isi_surat' => 'nullable|string',
            'file_surat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tanggal_surat.required' => 'Tanggal surat harus diisi',
            'tujuan.required' => 'Tujuan surat harus diisi',
            'perihal.required' => 'Perihal harus diisi',
            'file_surat.mimes' => 'File harus berformat PDF, JPG, atau PNG',
            'file_surat.max' => 'Ukuran file tidak boleh lebih dari 5MB',
        ];
    }
}
