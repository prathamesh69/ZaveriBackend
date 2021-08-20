<?php

namespace App\Http\Controllers;

use App\Helpers\Auth;
use App\UserContact;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserContactController extends Controller
{
    /**
     * List contacts.
     * GET /api/contacts
     */
    public function index(Request $req)
    {
        $contacts = UserContact::orderByRaw("
            (SELECT id FROM users WHERE users.mobile = user_contacts.mobile LIMIT 1) IS NOT NULL DESC, 
            (SELECT id FROM wholesaler_firms WHERE wholesaler_firms.mobile = user_contacts.mobile LIMIT 1) IS NOT NULL DESC, 
            name ASC
        ");

        if ($query = $req->query('query', false)) {
            $contacts->whereRaw('(name LIKE ? OR mobile LIKE ?)', ["%$query%", "%$query%"]);
        }

        if (!Auth::isAdmin()) {
            $contacts->where('user_id', Auth::id());
        } else if ($user_id = $req->query('user_id', false)) {
            $contacts->where('user_id', $user_id);
        }

        if ($device_id = $req->query('device_id', false)) {
            $contacts->where('device_id', $device_id);
        }

        if ($registered = $req->query('registered', false)) {
            if ($registered == '1') {
                if (Auth::isWholesaler()) {
                    $contacts->whereRaw("(SELECT COUNT(*) FROM users WHERE users.username = user_contacts.mobile AND role = 'retailer') > 0");
                } else {
                    $contacts->whereRaw("( (SELECT COUNT(*) FROM users WHERE users.username = user_contacts.mobile) > 0 OR (SELECT COUNT(*) FROM wholesaler_firms WHERE wholesaler_firms.mobile = user_contacts.mobile) > 0)");
                }
            } else if ($registered == '0') {
                if (Auth::isWholesaler()) {
                    $contacts->whereRaw("(SELECT COUNT(*) FROM users WHERE users.username = user_contacts.mobile AND role = 'retailer') = 0");
                } else {
                    $contacts->whereRaw("( (SELECT COUNT(*) FROM users WHERE users.username = user_contacts.mobile) = 0 AND (SELECT COUNT(*) FROM wholesaler_firms WHERE wholesaler_firms.mobile = user_contacts.mobile) = 0 )");
                }
            }
        }


        if ($req->query('nopaginate', false) == '1') {
            $contacts = $contacts->get();
        } else {
            $contacts = $contacts->paginate($req->query('per_page', 20));
        }

        if ($contacts->isEmpty()) return Utils::error('No contacts found!', 404);
        return $contacts;
    }

    /**
     * Create contact.
     * POST /api/contacts
     */
    public function create(Request $req)
    {
        if (!Auth::isAdmin()) $req->merge(['user_id' => Auth::id()]);

        $this->validate($req, [
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string',
            'mobile' => 'required|string',
            'device_id' => 'nullable|string',
        ]);

        // create contact
        $contact = new UserContact;
        $contact->fill($req->all());
        $contact->save();

        return $contact;
    }

    /**
     * Create multiple contacts.
     * POST /api/contacts/sync
     */
    public function sync(Request $req)
    {
        if (!Auth::isAdmin()) $req->merge(['user_id' => Auth::id()]);

        $this->validate($req, [
            'user_id' => 'required|exists:users,id',
            'contacts' => 'required|array',
        ]);

        $contacts = [];

        // create contacts
        foreach ($req->input('contacts') as $c) {
            if (empty($c['name']) || empty($c['mobile']) || !isset($c['device_id'])) continue;

            $contacts[] = [
                'name' => $c['name'],
                'mobile' => $c['mobile'],
                'user_id' => $req->input('user_id'),
                'device_id' => $c['device_id'],
            ];
        }

        $chunks = array_chunk($contacts, 1000);
        foreach ($chunks as $chunk) {
            UserContact::insertOrIgnore($chunk);
        }

        return ['message' => 'success'];
    }

    /**
     * Show contact.
     * GET /api/contacts/{id}
     */
    public function show(Request $req, $id)
    {
        $contact = UserContact::find($id);
        if (empty($contact)) Utils::error('Contact not found!', 404);
        return $contact;
    }

    /**
     * Update contact.
     * POST /api/contacts/{id}
     */
    public function update(Request $req, $id)
    {
        $contact = UserContact::find($id);
        if (empty($contact)) Utils::error('Contact not found!', 404);

        $this->validate($req, [
            'name' => 'string',
            'mobile' => 'string',
            'device_id' => 'nullable|string',
        ]);

        // update contact
        if ($req->has('name')) $contact->name = $req->input('name');
        if ($req->has('mobile')) $contact->mobile = $req->input('mobile');
        if ($req->has('device_id')) $contact->device_id = $req->input('device_id');
        $contact->save();

        return $contact;
    }

    /**
     * Delete contact by id.
     * DELETE /api/contacts/{id}
     */
    public function delete(Request $req, $id)
    {
        $contact = UserContact::find($id);
        if (empty($contact)) return Utils::error('Contact not found!', 404);
        $contact->delete();
        return $contact;
    }
}
