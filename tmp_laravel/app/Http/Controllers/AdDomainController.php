<?php

namespace App\Http\Controllers;

use App\Models\AdDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdDomainController extends Controller
{
    public function index()
    {
        $domains = AdDomain::all();
        return view('admin.domains.index', compact('domains'));
    }

    public function create()
    {
        return view('admin.domains.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hosts' => 'required|string|max:255',
            'base_dn' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'port' => 'required|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();
        $validated['use_ssl'] = $request->has('use_ssl');
        $validated['use_tls'] = $request->has('use_tls');

        AdDomain::create($validated);

        return redirect()->route('admin.domains.index')->with('success', 'دامین جدید با موفقیت اضافه شد.');
    }

    public function edit(AdDomain $domain)
    {
        return view('admin.domains.edit', compact('domain'));
    }

    public function update(Request $request, AdDomain $domain)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hosts' => 'required|string|max:255',
            'base_dn' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'port' => 'required|integer',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = $request->password;
        }

        $validated['use_ssl'] = $request->has('use_ssl');
        $validated['use_tls'] = $request->has('use_tls');

        $domain->update($validated);

        return redirect()->route('admin.domains.index')->with('success', 'دامین با موفقیت ویرایش شد.');
    }

    public function destroy(AdDomain $domain)
    {
        $domain->delete();
        return redirect()->route('admin.domains.index')->with('success', 'دامین حذف شد.');
    }
}
