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
        $password = $request->input('password');
        $domainId = $request->input('domain');

        // Local Admin login (fallback)
        if (empty($domainId) || $domainId === 'local') {
            if (Auth::attempt(['username' => $username, 'password' => $password])) {
                return redirect()->intended('/dashboard');
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

            // LdapRecord utilizes 'use_tls' for the `ldaps://` protocol (typically on port 636)
            // and 'use_starttls' for `ldap://` (typically on port 389 with an upgrade).
            // In our UI, `use_ssl` essentially maps to LdapRecord's `use_tls` configuration
            // flag (which handles ldaps:// protocols). We pass 'use_tls' if either SSL or TLS is checked.
            if ($adDomain->use_ssl || $adDomain->use_tls) {
                $config['use_tls'] = true;
            }

            $connection = new Connection($config);

            Container::addConnection($connection, $adDomain->slug);

            // Connect and authenticate the user
            $connection->connect();

            // Note: In real AD, you might need to append the domain name like user@domain.local
            // It depends on the AD configuration. Often, just the username works if bind format is setup,
            // or we might need to search for the user first.
            // For simplicity, we assume we search the user by sAMAccountName

            $ldapUser = $connection->query()->where('sAMAccountName', '=', $username)->first();

            if (!$ldapUser) {
                 return back()->withErrors(['username' => 'کاربر در Active Directory یافت نشد.']);
            }

            // Attempt bind with the user's distinguished name and provided password
            if ($connection->auth()->attempt($ldapUser->getDn(), $password)) {
                // Successful AD authentication, create or update local user
                $user = User::updateOrCreate(
                    ['username' => $username],
                    [
                        'name' => $ldapUser->getFirstAttribute('cn') ?? $username,
                        'email' => $ldapUser->getFirstAttribute('mail') ?? null,
                        'password' => null, // Managed by AD
                    ]
                );

                Auth::login($user);
                return redirect()->intended('/dashboard');
            }

            return back()->withErrors(['password' => 'رمز عبور Active Directory اشتباه است.']);

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
