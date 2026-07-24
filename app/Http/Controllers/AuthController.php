<?php
namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller {
    public function showLogin() {
        if (AuthService::hasCredentials() && Session::has('user_id')) return redirect('/console');
        return view('auth.login');
    }

    public function setup(Request $request) {
        $request->validate(['username' => 'required|min:3|max:50', 'password' => 'required|min:8']);
        if (AuthService::hasCredentials()) return response()->json(['error' => 'User already exists. Login instead.'], 409);
        $user = AuthService::createUser($request->username, $request->password);
        return response()->json(['success' => true, 'message' => 'Account created. You can now login.', 'userId' => $user->id]);
    }

    public function login(Request $request) {
        $request->validate(['username' => 'required', 'password' => 'required']);
        $user = AuthService::login($request->username, $request->password);
        if (!$user) return response()->json(['error' => 'Invalid credentials'], 401);
        Session::put('user_id', $user->id);
        Session::put('username', $user->username);
        return response()->json(['success' => true, 'user' => ['id' => $user->id, 'username' => $user->username]]);
    }

    public function logout() {
        Session::flush();
        return redirect('/login');
    }

    public function saveCredentials(Request $request) {
        $request->validate(['meta_access_token' => 'required', 'meta_account_id' => 'required', 'anthropic_api_key' => 'required']);
        $id = AuthService::saveCredentials($request->meta_access_token, $request->meta_account_id, $request->anthropic_api_key);
        return response()->json(['success' => true, 'message' => 'Credentials saved encrypted.', 'id' => $id]);
    }

    public function getCredentialsStatus() {
        return response()->json(['configured' => AuthService::hasCredentials()]);
    }
}
