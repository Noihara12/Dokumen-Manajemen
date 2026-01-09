<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuratMasukRequest extends FormRequest
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
            'nomor_surat' => 'required|string|max:50|unique:surat_masuk,nomor_surat',
            'tanggal_surat' => 'required|date',
            'tanggal_diterima' => 'required|date|after_or_equal:tanggal_surat',
            'pengirim' => 'required|string|max:255',
            'perihal' => 'required|string|max:500',
            'file_surat' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nomor_surat.required' => 'Nomor surat harus diisi',
            'nomor_surat.unique' => 'Nomor surat sudah terdaftar',
            'tanggal_surat.required' => 'Tanggal surat harus diisi',
            'tanggal_diterima.required' => 'Tanggal diterima harus diisi',
            'tanggal_diterima.after_or_equal' => 'Tanggal diterima tidak boleh lebih awal dari tanggal surat',
            'pengirim.required' => 'Pengirim harus diisi',
            'perihal.required' => 'Perihal harus diisi',
            'file_surat.required' => 'File surat harus diunggah',
            'file_surat.mimes' => 'File harus berformat PDF, JPG, atau PNG',
            'file_surat.max' => 'Ukuran file tidak boleh lebih dari 5MB',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nomor_surat' => 'Nomor Surat',
            'tanggal_surat' => 'Tanggal Surat',
            'tanggal_diterima' => 'Tanggal Diterima',
            'pengirim' => 'Pengirim',
            'perihal' => 'Perihal',
            'klasifikasi_surat_id' => 'Klasifikasi Surat',
            'unit_kerja_id' => 'Unit Kerja Tujuan',
            'file_surat' => 'File Surat',
        ];
    }
}
