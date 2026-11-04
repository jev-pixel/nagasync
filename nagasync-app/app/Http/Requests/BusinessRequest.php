<?php

namespace App\Http\Requests;

use App\Models\Business;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership is checked in the controller
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:120'],
            'category'     => ['required', Rule::in(Business::CATEGORIES)],
            'tagline'      => ['nullable', 'string', 'max:140'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'barangay_id'  => ['required', 'exists:barangays,id'],
            'address'      => ['required', 'string', 'max:255'],
            'latitude'     => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'phone'        => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\-\s]{6,20}$/'],
            'email'        => ['nullable', 'email', 'max:120'],
            'website'      => ['nullable', 'url:http,https', 'max:255'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:255'],
            'hours'        => ['nullable', 'string', 'max:255'],
            'price_range'  => ['nullable', 'integer', 'between:1,3'],
            'tags'         => ['nullable', 'array', 'max:8'],
            'tags.*'       => [Rule::in(Business::TAGS)],
            'permit_no'    => ['nullable', 'string', 'max:60'],
            'cover'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                               'dimensions:min_width=400,min_height=300,max_width=5000,max_height=5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'website.url'      => 'Enter a full web address starting with http:// or https://.',
            'facebook_url.url' => 'Enter a full web address starting with http:// or https://.',
            'phone.regex'      => 'Use digits, spaces, + ( ) and - only.',
            'cover.dimensions' => 'The photo must be between 400×300 and 5000×5000 pixels.',
            'cover.max'        => 'The photo must be 2 MB or smaller.',
        ];
    }
}