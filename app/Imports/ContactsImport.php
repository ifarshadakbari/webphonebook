<?php

namespace App\Imports;

use App\Models\Contact;
use App\Models\ContactPhone;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ContactsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Skip header row
        $rows->shift();

        foreach ($rows as $row) {
            // Check if essential fields exist (e.g., name and last_name)
            if (!isset($row[1]) || !isset($row[2])) {
                continue;
            }

            $socialTitle = in_array($row[0], ['آقای', 'خانم']) ? $row[0] : 'آقای';

            $contact = Contact::create([
                'user_id' => Auth::id(),
                'social_title' => $socialTitle,
                'first_name' => $row[1],
                'last_name' => $row[2],
                'mobile' => $row[3] ?? null,
            ]);

            // Handling phones
            // Assuming column 4 is phone, column 5 is internal (or parsing from column 4)
            // Let's assume Col 4 is phone, Col 5 is internal
            if (isset($row[4]) && !empty(trim($row[4]))) {
                ContactPhone::create([
                    'contact_id' => $contact->id,
                    'phone_number' => $row[4],
                    'internal_number' => $row[5] ?? null,
                ]);
            }
        }
    }
}
