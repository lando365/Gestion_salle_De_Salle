<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MemberRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'subscription_type' => 'required|in:monthly,quarterly,yearly,dropin',
            'subscription_end' => 'required|date',
            'status' => 'required|in:active,inactive,expired',
        ];
    }
}