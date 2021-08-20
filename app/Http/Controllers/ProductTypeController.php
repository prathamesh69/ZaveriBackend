<?php

namespace App\Http\Controllers;

use App\ProductType;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    /**
     * List types.
     * GET /api/product-types
     */
    public function index(Request $req)
    {
        $types = ProductType::latest();

        if ($query = $req->query('query', false)) {
            $types->where('name', 'like', "%$query%");
        }

        if ($req->query('nopaginate', false) == '1') {
            $types = $types->get();
        } else {
            $types = $types->paginate($req->query('per_page', 20));
        }

        if ($types->isEmpty()) return Utils::error('No types found!', 404);
        return $types;
    }

    /**
     * Create type.
     * POST /api/product-types
     */
    public function create(Request $req)
    {
        $this->validate($req, [
            'name' => 'required',
        ]);

        // create type
        $type = new ProductType;
        $type->fill($req->all());
        $type->save();

        return $type;
    }

    /**
     * Show type.
     * GET /api/product-types/{id}
     */
    public function show(Request $req, $id)
    {
        $type = ProductType::find($id);
        if (empty($type)) Utils::error('Type not found!', 404);
        return $type;
    }

    /**
     * Update type.
     * POST /api/product-types/{id}
     */
    public function update(Request $req, $id)
    {
        $type = ProductType::find($id);
        if (empty($type)) Utils::error('Type not found!', 404);

        $this->validate($req, [
            'name' => 'string',
        ]);

        // update type
        if ($req->has('name')) $type->name = $req->input('name');
        $type->save();

        return $type;
    }

    /**
     * Delete type by id.
     *
     * DELETE /api/product-types/{id}
     */
    public function delete(Request $req, $id)
    {
        $type = ProductType::find($id);
        if (empty($type)) return Utils::error('Type not found!', 404);
        $type->delete();
        return $type;
    }
}
