<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'barangays' => Barangay::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Accept "0917 123 4567", "0917-123-4567", "+63 917 123 4567", ...
        $request->merge(['phone' => preg_replace('/[\s\-()]/', '', (string) $request->input('phone'))]);

        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'phone'           => ['required', 'regex:/^(\+?63|0)9\d{9}$/'],
            'barangay_id'     => ['required', 'exists:barangays,id'],
            'privacy_consent' => ['accepted'],
            'password'        => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'phone.regex'                => 'Enter a valid Philippine mobile number, e.g. 0917 123 4567.',
            'privacy_consent.accepted'   => 'You must accept the privacy notice to register.',
        ]);

        $user = new User([
            'name'        => $request->name,
            'email'       => $request->email,
            'phone'       => $this->normalizePhone($request->phone),
            'barangay_id' => $request->barangay_id,
            'password'    => Hash::make($request->password),
        ]);

        // Server-controlled fields are not mass-assignable
        $user->forceFill([
            'privacy_consent_at'     => now(),
            'privacy_notice_version' => config('nagasync.privacy_notice_version'),
        ])->save();

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    /** "09171234567" / "639171234567" / "+639171234567" -> "+639171234567" */
    private function normalizePhone(string $phone): string
    {
        $digits = ltrim($phone, '+');

        return '+63' . substr($digits, str_starts_with($digits, '0') ? 1 : 2);
    }
}