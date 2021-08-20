<?php

namespace App\Http\Controllers;

use App\Preference;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    /**
     * List preferences.
     * GET /api/preferences
     */
    public function index(Request $req)
    {
        $preferences = Preference::latest();

        if ($query = $req->query('query', false)) {
            $preferences->where('value', 'like', "%$query%");
        }

        if ($req->query('nopaginate', false) == '1') {
            $preferences = $preferences->get();
        } else {
            $preferences = $preferences->paginate($req->query('per_page', 20));
        }

        if ($preferences->isEmpty()) return Utils::error('No preferences found!', 404);
        return $preferences;
    }

    /**
     * Show preference.
     * GET /api/preferences/{id}
     */
    public function show(Request $req, $id)
    {
        $preference = Preference::find($id);
        if (empty($preference)) Utils::error('Preference not found!', 404);
        return $preference;
    }

    /**
     * Update preference.
     * POST /api/preferences/{id}
     */
    public function update(Request $req, $id)
    {
        $preference = Preference::find($id);
        if (empty($preference)) Utils::error('Preference not found!', 404);

        $this->validate($req, [
            'value' => 'nullable|string',
        ]);

        // update preference
        if ($req->has('value')) $preference->value = $req->input('value');
        $preference->save();

        return $preference;
    }
}
