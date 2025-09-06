<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMitraRequest extends FormRequest
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
            'nama' => 'required|string|max:255',
            'no_telp' => 'required|string|max:20|unique:mitras,no_telp',
            'tanggal_lead' => 'required|date',
            'user_id' => 'nullable|exists:users,id',
            'brand_id' => 'required|exists:brands,id',
            'label_id' => 'nullable|exists:labels,id',
            'chat' => 'required|in:masuk,followup',
            'kota' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
            'komentar' => 'nullable|string',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama mitra wajib diisi.',
            'nama.string' => 'Nama mitra harus berupa teks.',
            'nama.max' => 'Nama mitra maksimal 255 karakter.',
            
            'no_telp.required' => 'Nomor telepon wajib diisi.',
            'no_telp.string' => 'Nomor telepon harus berupa teks.',
            'no_telp.max' => 'Nomor telepon maksimal 20 karakter.',
            'no_telp.unique' => 'Nomor telepon sudah terdaftar untuk mitra lain. Silakan gunakan nomor telepon yang berbeda.',
            
            'tanggal_lead.required' => 'Tanggal lead wajib diisi.',
            'tanggal_lead.date' => 'Tanggal lead harus berupa tanggal yang valid.',
            
            'brand_id.required' => 'Brand wajib dipilih.',
            'brand_id.exists' => 'Brand yang dipilih tidak valid.',
            
            'label_id.exists' => 'Label yang dipilih tidak valid.',
            
            'chat.required' => 'Status chat wajib dipilih.',
            'chat.in' => 'Status chat harus berupa "masuk" atau "followup".',
            
            'kota.string' => 'Kota harus berupa teks.',
            'kota.max' => 'Kota maksimal 255 karakter.',
            
            'provinsi.string' => 'Provinsi harus berupa teks.',
            'provinsi.max' => 'Provinsi maksimal 255 karakter.',
            
            'komentar.string' => 'Komentar harus berupa teks.',
        ];
    }
}
