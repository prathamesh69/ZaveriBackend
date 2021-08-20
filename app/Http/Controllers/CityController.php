<?php

namespace App\Http\Controllers;

use App\City;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CityController extends Controller
{
    /**
     * List cities.
     * GET /api/cities
     */
    public function index(Request $req)
    {
        $cities = City::select('*')
            ->selectRaw("(SELECT COUNT(*) FROM users WHERE role = 'retailer' AND city_id = cities.id) AS retailer_count")
            ->selectRaw("(SELECT COUNT(*) FROM wholesaler_firms WHERE city_id = cities.id) AS wholesaler_firm_count")
            ->orderBy('name');

        if ($query = $req->query('query', false)) {
            $cities->whereRaw('(name LIKE ?)', ["%$query%"]);
        }

        if ($state = $req->query('state', false)) {
            $cities->whereIn('state', explode(',', $state));
        }

        if ($req->query('nopaginate', false) == '1') {
            $cities = $cities->get();
        } else {
            $cities = $cities->paginate($req->query('per_page', 20));
        }

        if ($cities->isEmpty()) Utils::error('No cities found!', 404);
        return $cities;
    }

    /**
     * Create city.
     * POST /api/cities
     */
    public function create(Request $req)
    {
        $this->validate($req, [
            'name' => 'required|string|max:255',
            'state' => 'required|string|max:255',
        ]);

        // create city
        $city = new City;
        $city->fill($req->all());
        $city->save();

        return $city;
    }

    /**
     * Show city.
     * GET /api/cities/{id}
     */
    public function show(Request $req, $id)
    {
        $city = City::find($id);
        if (empty($city)) Utils::error('City not found!', 404);
        return $city;
    }

    /**
     * Update city.
     * POST /api/cities/{id}
     */
    public function update(Request $req, $id)
    {
        $city = City::find($id);

        if (empty($city)) Utils::error('City not found!', 404);

        $this->validate($req, [
            'name' => 'string|max:255',
            'state' => 'string|max:255',
        ]);

        // update city
        if ($req->has('name')) $city->name = $req->input('name');
        if ($req->has('state')) $city->state = $req->input('state');
        $city->save();

        return $city;
    }

    /**
     * Delete city by id.
     * DELETE /api/cities/{id}
     */
    public function delete(Request $req, $id)
    {
        $city = City::find($id);
        if (empty($city)) Utils::error('City not found!', 404);
        $city->delete();
        return $city;
    }


    /**
     * List states.
     * GET /api/cities/states
     */
    public function states(Request $req)
    {
        $states = City::distinct('state')->orderBy('state')->pluck('state');

        if ($req->query('fill_cities', false) == '1') {
            $states = collect($states)->map(function ($state) {
                $cities = City::where('state', $state)->orderBy('name')->get();
                return ['name' => $state, 'cities' => $cities];
            });
        }

        if ($states->isEmpty()) Utils::error('No states found!', 404);
        return $states;
    }
}
