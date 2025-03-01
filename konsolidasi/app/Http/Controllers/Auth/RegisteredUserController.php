<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{

    public function checkUsername(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
        ]);

        $exists = User::where('username', $request->query('username'))->exists();

        return response()->json(['exists' => $exists]);
    }

    /**
     * Display the registration view.
     */

    public function create(): View
    {
        $wilayah = Wilayah::all(); // Fetch all wilayah records
        return view('auth.register', compact('wilayah'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
//    public function store(Request $request): RedirectResponse
//    {
//        $request->validate([
//            'name' => ['required', 'string', 'max:255'],
//            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
//            'password' => ['required', 'confirmed', Rules\Password::defaults()],
//        ]);
//
//        $user = User::create([
//            'name' => $request->name,
//            'email' => $request->email,
//            'password' => Hash::make($request->password),
//        ]);
//
//        event(new Registered($user));
//
//        Auth::login($user);
//
//        return redirect(route('dashboard', absolute: false));
//    }

    public function store(Request $request): RedirectResponse
    {

//        dd($request->all());

//        $request->validate([
//            'username' => ['required', 'string', 'max:255', 'unique:user,username'],
//            'password' => ['required', 'confirmed', Rules\Password::defaults()],
//            'nama_lengkap' => ['required', 'string', 'max:255'],
//            'is_pusat' => ['required', 'boolean'],
//            'kd_wilayah' => ['nullable', 'exists:wilayah,kd_wilayah'], // Optional field, must exist in Wilayah table
//        ]);

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'nama_lengkap' => $request->nama_lengkap,
            'is_pusat' => $request->is_pusat,
            'kd_wilayah' => $request->kd_wilayah,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

}
