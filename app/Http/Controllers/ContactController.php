<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Contact::with('phones');

            // Regular user only sees their own contacts
            if (!auth()->user()->is_admin) {
                $query->where('user_id', auth()->id());
            }

            return DataTables::of($query)
                ->addColumn('photo', function ($contact) {
                    $defaultImage = $contact->social_title === 'خانم' ? 'default-female.png' : 'default-male.png';
                    $url = $contact->photo ? asset('storage/photos/'.$contact->photo) : asset('assets/'.$defaultImage);
                    return '<img src="'.$url.'" class="contact-photo" alt="Photo">';
                })
                ->addColumn('full_name', function ($contact) {
                    return $contact->social_title . ' ' . $contact->first_name . ' ' . $contact->last_name;
                })
                ->filterColumn('full_name', function($query, $keyword) {
                    $query->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$keyword}%"]);
                })
                ->orderColumn('full_name', function ($query, $order) {
                    $query->orderBy('first_name', $order)->orderBy('last_name', $order);
                })
                ->addColumn('phones', function ($contact) {
                    $html = '<ul>';
                    foreach($contact->phones as $phone) {
                        $phone_number = htmlspecialchars($phone->phone_number);
                        $internal_number = htmlspecialchars($phone->internal_number);
                        $internal = $internal_number ? ' (داخلی: '.$internal_number.')' : '';
                        $html .= '<li dir="ltr" class="text-end">'.$phone_number.$internal.'</li>';
                    }
                    $html .= '</ul>';
                    return $html;
                })
                ->addColumn('action', function ($contact) {
                    $editUrl = route('contacts.edit', $contact->id);
                    $deleteUrl = route('contacts.destroy', $contact->id);

                    return '
                        <a href="'.$editUrl.'" class="btn btn-sm btn-warning mb-1"><i class="fa fa-edit"></i></a>
                        <form action="'.$deleteUrl.'" method="POST" class="d-inline" onsubmit="return confirm(\'مطمئن هستید؟\');">
                            '.csrf_field().'
                            '.method_field('DELETE').'
                            <button type="submit" class="btn btn-sm btn-danger mb-1"><i class="fa fa-trash"></i></button>
                        </form>
                    ';
                })
                ->rawColumns(['photo', 'phones', 'action'])
                ->make(true);
        }

        return view('contacts.index');
    }

    public function create()
    {
        return view('contacts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'social_title' => 'required|in:آقای,خانم',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'phones' => 'array',
            'phones.*.number' => 'required_with:phones|string',
            'phones.*.internal' => 'nullable|string',
        ]);

        $photoName = null;
        if ($request->hasFile('photo')) {
            $image = $request->file('photo');
            $extension = $image->extension();
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                $extension = 'png';
            }
            $photoName = Str::random(20) . '.' . $extension;

            $manager = new ImageManager(new Driver());
            $img = $manager->read($image->getRealPath());
            $img->cover(300, 300);

            if (!Storage::disk('public')->exists('photos')) {
                Storage::disk('public')->makeDirectory('photos');
            }

            $img->save(storage_path('app/public/photos/' . $photoName));
        }

        $contact = Contact::create([
            'user_id' => auth()->id(),
            'social_title' => $request->social_title,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'mobile' => $request->mobile,
            'photo' => $photoName,
        ]);

        if ($request->has('phones')) {
            foreach ($request->phones as $phone) {
                if(!empty($phone['number'])){
                    ContactPhone::create([
                        'contact_id' => $contact->id,
                        'phone_number' => $phone['number'],
                        'internal_number' => $phone['internal'] ?? null,
                    ]);
                }
            }
        }

        if ($request->ajax()) {
            return response()->json(['success' => 'مخاطب با موفقیت ذخیره شد.']);
        }

        return redirect()->route('contacts.index')->with('success', 'مخاطب با موفقیت ذخیره شد.');
    }

    public function edit(Contact $contact)
    {
        if (!auth()->user()->is_admin && $contact->user_id != auth()->id()) {
            abort(403);
        }

        return view('contacts.edit', compact('contact'));
    }

    public function update(Request $request, Contact $contact)
    {
        if (!auth()->user()->is_admin && $contact->user_id != auth()->id()) {
            abort(403);
        }

        $request->validate([
            'social_title' => 'required|in:آقای,خانم',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'phones' => 'array',
            'phones.*.number' => 'required_with:phones|string',
            'phones.*.internal' => 'nullable|string',
        ]);

        $photoName = $contact->photo;
        if ($request->hasFile('photo')) {
            $image = $request->file('photo');
            $extension = $image->extension();
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                $extension = 'png';
            }
            $photoName = Str::random(20) . '.' . $extension;

            $manager = new ImageManager(new Driver());
            $img = $manager->read($image->getRealPath());
            $img->cover(300, 300);

            if (!Storage::disk('public')->exists('photos')) {
                Storage::disk('public')->makeDirectory('photos');
            }

            $img->save(storage_path('app/public/photos/' . $photoName));

            // Delete old photo
            if ($contact->photo && Storage::disk('public')->exists('photos/' . $contact->photo)) {
                Storage::disk('public')->delete('photos/' . $contact->photo);
            }
        }

        $contact->update([
            'social_title' => $request->social_title,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'mobile' => $request->mobile,
            'photo' => $photoName,
        ]);

        $contact->phones()->delete();
        if ($request->has('phones')) {
            foreach ($request->phones as $phone) {
                if(!empty($phone['number'])){
                    ContactPhone::create([
                        'contact_id' => $contact->id,
                        'phone_number' => $phone['number'],
                        'internal_number' => $phone['internal'] ?? null,
                    ]);
                }
            }
        }

        if ($request->ajax()) {
            return response()->json(['success' => 'مخاطب با موفقیت ویرایش شد.']);
        }

        return redirect()->route('contacts.index')->with('success', 'مخاطب با موفقیت ویرایش شد.');
    }

    public function destroy(Contact $contact)
    {
        if (!auth()->user()->is_admin && $contact->user_id != auth()->id()) {
            abort(403);
        }

        if ($contact->photo && Storage::disk('public')->exists('photos/' . $contact->photo)) {
            Storage::disk('public')->delete('photos/' . $contact->photo);
        }

        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'مخاطب حذف شد.');
    }

    public function import(Request $request) {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\ContactsImport, $request->file('file'));
            return redirect()->route('contacts.index')->with('success', 'مخاطبین با موفقیت درون‌ریزی شدند.');
        } catch (\Exception $e) {
            return redirect()->route('contacts.index')->with('error', 'خطا در درون‌ریزی فایل: ' . $e->getMessage());
        }
    }

    public function export() {
        $userId = auth()->user()->is_admin ? null : auth()->id();
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ContactsExport($userId), 'contacts.xlsx');
    }
}
