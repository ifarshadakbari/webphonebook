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

            // Most AD servers require the UPN (username@domain.com) or DOMAIN\username to bind if not using a DN.
            // If we didn't find the user (no DN), we append the domain part derived from base_dn if needed.
            // If the admin uses domain 'parszarasa.local', base_dn is likely 'dc=parszarasa,dc=local'.
            // To be safe and simple, let's try the username first, and if it fails, try adding the UPN suffix.
            $bindUsername = ($ldapUser && is_object($ldapUser) && method_exists($ldapUser, 'getDn')) ? $ldapUser->getDn() : $username;

            // Extract a domain from base_dn (e.g. dc=parszarasa,dc=local => parszarasa.local)
            $domainSuffix = '';
            if (preg_match_all('/dc=([^,]+)/i', $adDomain->base_dn, $matches)) {
                $domainSuffix = implode('.', $matches[1]);
            }
            $upn = $username . '@' . $domainSuffix;

            // First try pure username (which some ADs accept if default domain is configured),
            // if not try UPN, if not try DN if we have it.
            $authSuccess = false;

            if (!empty($domainSuffix) && $connection->auth()->attempt($upn, $password)) {
                $authSuccess = true;
            } elseif ($connection->auth()->attempt($username, $password)) {
                $authSuccess = true;
            } elseif ($bindUsername !== $username && $connection->auth()->attempt($bindUsername, $password)) {
                $authSuccess = true;
            }

            if ($authSuccess) {
                // Successful AD authentication, create or update local user
                $name = ($ldapUser && is_object($ldapUser) && method_exists($ldapUser, 'getFirstAttribute')) ? ($ldapUser->getFirstAttribute('cn') ?? $username) : $username;
                $email = ($ldapUser && is_object($ldapUser) && method_exists($ldapUser, 'getFirstAttribute')) ? ($ldapUser->getFirstAttribute('mail') ?? null) : null;

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
