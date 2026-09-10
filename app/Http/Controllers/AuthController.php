<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AdDomain;
use App\Models\User;
use LdapRecord\Container;
use LdapRecord\Connection;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        $domains = AdDomain::all();
        return view('auth.login', compact('domains'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'domain' => 'nullable' // null implies local login
        ]);

        $username = $request->input('username');

        // Ensure username is clean of backslash syntax like "domain\username" if user provided it
        if (strpos($username, '\\') !== false) {
            $parts = explode('\\', $username);
            $username = end($parts);
        }

        $password = $request->input('password');
        $domainId = $request->input('domain');

        // Local Admin login (fallback)
        if (empty($domainId) || $domainId === 'local') {
            if (Auth::attempt(['username' => $username, 'password' => $password])) {
                return redirect()->intended(route('contacts.index'));
            }
            return back()->withErrors(['username' => 'نام کاربری یا رمز عبور اشتباه است (لاگین محلی).']);
        }

        // Active Directory Login
        $adDomain = AdDomain::find($domainId);
        if (!$adDomain) {
            return back()->withErrors(['domain' => 'دامین انتخاب شده معتبر نیست.']);
        }

        try {
            // Build the configuration array dynamically based on LdapRecord constraints
            $config = [
                'hosts'    => explode(',', $adDomain->hosts),
                'base_dn'  => $adDomain->base_dn,
                'username' => $adDomain->username,
                'password' => $adDomain->password,
                'port'     => $adDomain->port,
            ];

            // LdapRecord v3/v4 handles SSL via protocol/TLS options rather than 'use_ssl' key
            if ($adDomain->use_ssl) {
                $config['use_tls'] = true; // Use TLS flag for ldaps connections internally in LdapRecord
                // Often SSL implies port 636 but let's stick to their port and TLS boolean
            } elseif ($adDomain->use_tls) {
                $config['use_tls'] = true;
            }

            $connection = new Connection($config);

            Container::addConnection($connection, $adDomain->slug);

            // Connect and authenticate the user
            try {
                $connection->connect();
            } catch (\Exception $e) {
                // Ignore initial bind errors, we will fallback to attempting to bind as the user
                // Some AD setups do not allow anonymous binds or the service account is invalid.
            }

            // If a service account is configured correctly, we search the user.
            // If the search fails or the bind above fails, we'll try a direct bind with the provided username and password.
            $ldapUser = null;
            try {
                $ldapUser = $connection->query()->where('sAMAccountName', '=', $username)->first();
            } catch (\Exception $e) {}

            $bindUsername = $ldapUser ? $ldapUser->getDn() : $username;

            // If username doesn't contain a domain component, it might need one (like user@domain.local)
            // depending on AD. LdapRecord auth()->attempt() tries to bind.

            // Attempt bind with the user's distinguished name (or raw username) and provided password
            if ($connection->auth()->attempt($bindUsername, $password)) {
                // Successful AD authentication, create or update local user
                $name = $ldapUser ? ($ldapUser->getFirstAttribute('cn') ?? $username) : $username;
                $email = $ldapUser ? ($ldapUser->getFirstAttribute('mail') ?? null) : null;

                $user = User::updateOrCreate(
                    ['username' => $username],
                    [
                        'name' => $name,
                        'email' => $email,
                        'password' => null, // Managed by AD
                    ]
                );

                Auth::login($user);
                return redirect()->intended(route('contacts.index'));
            }

            return back()->withErrors(['password' => 'ورود ناموفق. نام کاربری یا رمز عبور Active Directory اشتباه است یا ارتباط برقرار نشد.']);

        } catch (\Exception $e) {
            return back()->withErrors(['domain' => 'خطا در ارتباط با سرور Active Directory: ' . $e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
