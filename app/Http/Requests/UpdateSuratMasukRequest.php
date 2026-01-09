<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSuratMasukRequest extends FormRequest
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
        $suratId = $this->route('id');

        return [
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date|after_or_equal:tanggal_surat',
            'pengirim' => 'required|string|max:255',
            'perihal' => 'required|string|max:500',
            'status' => 'required|in:diterima,diproses,selesai',
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
            'tanggal_diterima.required' => 'Tanggal diterima harus diisi',
            'pengirim.required' => 'Pengirim harus diisi',
            'perihal.required' => 'Perihal harus diisi',
            'status.required' => 'Status harus dipilih',
            'file_surat.mimes' => 'File harus berformat PDF, JPG, atau PNG',
            'file_surat.max' => 'Ukuran file tidak boleh lebih dari 5MB',
        ];
    }
}
