<?php
namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\Meta\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ProfileController extends Controller {
    public function index() {
        $userId = Session::get('user_id');
        if (!$userId) return redirect('/login');
        $profiles = AuthService::getProfiles($userId);
        $credentials = AuthService::getCredentials();
        return view('profiles.index', compact('profiles', 'credentials'));
    }

    public function create(Request $request) {
        $userId = Session::get('user_id');
        if (!$userId) return redirect('/login');

        $credentials = AuthService::getCredentials();
        if (!$credentials) return response()->json(['error' => 'Set API credentials first.'], 400);

        $accounts = [];
        try {
            $client = new Client($credentials['meta_access_token'], '');
            $response = $client->get('me/adaccounts', ['fields' => 'id,name,account_status,currency', 'limit' => 100]);
            if (isset($response['data'])) {
                $accounts = $response['data'];
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch ad accounts: ' . $e->getMessage()], 500);
        }

        return view('profiles.create', compact('accounts'));
    }

    public function store(Request $request) {
        $userId = Session::get('user_id');
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'name' => 'required|string|max:100',
            'meta_account_id' => 'required|string',
        ]);

        $existing = AuthService::getProfile($userId, $request->meta_account_id);
        if ($existing) return response()->json(['error' => 'Profile already exists for this account.'], 409);

        $credentials = AuthService::getCredentials();
        $metaAccountName = null;
        $metaCurrency = null;

        if ($credentials) {
            try {
                $client = new Client($credentials['meta_access_token'], $request->meta_account_id);
                $accountInfo = $client->getAccount();
                if (isset($accountInfo['name'])) $metaAccountName = $accountInfo['name'];
                if (isset($accountInfo['currency'])) $metaCurrency = $accountInfo['currency'];
            } catch (\Exception $e) {}
        }

        $profile = AuthService::createProfile($userId, $request->name, $request->meta_account_id, $metaAccountName, $metaCurrency);
        return redirect('/profiles');
    }

    public function select(Request $request, string $id) {
        $userId = Session::get('user_id');
        if (!$userId) return redirect('/login');

        $profile = AuthService::getProfile($userId, $id);
        if (!$profile) return redirect('/profiles');

        Session::put('profile_id', $profile->id);
        return redirect('/console');
    }

    public function destroy(Request $request, string $id) {
        $userId = Session::get('user_id');
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        AuthService::deleteProfile($userId, $id);
        if (Session::get('profile_id') === $id) Session::forget('profile_id');
        return redirect('/profiles');
    }
}
