<?php
namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller {
    public function showLogin() {
        if (Session::has('user_id')) return redirect('/profiles');
        return view('auth.login');
    }

    public function setup(Request $request) {
        $request->validate(['username' => 'required|min:3|max:50', 'password' => 'required|min:8']);
        if (Session::has('user_id')) return redirect('/profiles');
        $user = AuthService::createUser($request->username, $request->password);
        Session::put('user_id', $user->id);
        Session::put('username', $user->username);
        return redirect('/profiles');
    }

    public function login(Request $request) {
        $request->validate(['username' => 'required', 'password' => 'required']);
        $user = AuthService::login($request->username, $request->password);
        if (!$user) {
            if ($request->expectsJson()) return response()->json(['error' => 'Invalid credentials'], 401);
            return back()->withErrors(['username' => 'Invalid credentials']);
        }
        Session::put('user_id', $user->id);
        Session::put('username', $user->username);
        Session::forget('profile_id');
        if ($request->expectsJson()) return response()->json(['success' => true, 'user' => ['id' => $user->id, 'username' => $user->username]]);
        return redirect('/profiles');
    }

    public function logout() {
        Session::flush();
        return redirect('/login');
    }

    public function saveCredentials(Request $request) {
        $request->validate(['meta_access_token' => 'required', 'anthropic_api_key' => 'required']);
        $id = AuthService::saveCredentials($request->meta_access_token, $request->anthropic_api_key);
        return response()->json(['success' => true, 'message' => 'Credentials saved encrypted.', 'id' => $id]);
    }

    public function getCredentialsStatus() {
        return response()->json(['configured' => AuthService::hasCredentials()]);
    }
}
