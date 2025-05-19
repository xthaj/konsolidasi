<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\UserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function checkUsername(Request $request)
    {
        $query = User::where('username', $request->username);
        if ($request->has('except')) {
            $query->where('user_id', '!=', $request->except);
        }
        $exists = $query->exists();
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
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {

        // dd($request->all());

        // UserService to create user
        $result = $this->userService->createUser($request);

        if (!$result['success']) {
            return back()->withErrors($result['errors'])->withInput($result['input']);
        }

        $user = $result['user'];

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
