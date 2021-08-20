<?php

namespace App\Http\Controllers;

use App\Subcategory;
use App\Helpers\ImageHelper;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    /**
     * List subcategories.
     * GET /api/subcategories
     */
    public function index(Request $req)
    {
        $subcategories = Subcategory::latest();

        if ($query = $req->query('query', false)) {
            $subcategories->where('name', 'like', "%$query%");
        }

        if ($category_id = $req->query('category_id', false)) {
            $subcategories->where('category_id', $category_id);
        }

        if ($req->query('nopaginate', false) == '1') {
            $subcategories = $subcategories->get();
        } else {
            $subcategories = $subcategories->paginate($req->query('per_page', 20));
        }

        if ($subcategories->isEmpty()) return Utils::error('No subcategories found!', 404);
        return $subcategories;
    }

    /**
     * Create subcategory.
     * POST /api/subcategories
     */
    public function create(Request $req)
    {
        $this->validate($req, [
            'name' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image',
        ]);

        // create subcategory
        $subcategory = new Subcategory;
        $subcategory->fill($req->all());
        if ($req->hasFile('image')) {
            $subcategory->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_SUBCATEGORY);
        }
        $subcategory->save();

        return $subcategory;
    }

    /**
     * Show subcategory.
     * GET /api/subcategories/{id}
     */
    public function show(Request $req, $id)
    {
        $subcategory = Subcategory::find($id);
        if (empty($subcategory)) Utils::error('Subcategory not found!', 404);
        return $subcategory;
    }

    /**
     * Update subcategory.
     * POST /api/subcategories/{id}
     */
    public function update(Request $req, $id)
    {
        $subcategory = Subcategory::find($id);
        if (empty($subcategory)) Utils::error('Subcategory not found!', 404);

        $this->validate($req, [
            'name' => 'string',
            'category_id' => 'exists:categories,id',
            'image' => 'nullable|image',
        ]);

        // update subcategory
        if ($req->has('name')) $subcategory->name = $req->input('name');
        if ($req->has('category_id')) $subcategory->category_id = $req->input('category_id');
        if ($req->hasFile('image')) {
            if ($subcategory->image != null) ImageHelper::delete(ImageHelper::$TYPE_SUBCATEGORY, $subcategory->image);
            $subcategory->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_SUBCATEGORY);
        }
        $subcategory->save();

        return $subcategory;
    }

    /**
     * Delete subcategory by id.
     *
     * DELETE /api/subcategories/{id}
     */
    public function delete(Request $req, $id)
    {
        $subcategory = Subcategory::find($id);
        if (empty($subcategory)) return Utils::error('Subcategory not found!', 404);
        $subcategory->delete();
        if ($subcategory->image != null) ImageHelper::delete(ImageHelper::$TYPE_SUBCATEGORY, $subcategory->image);
        return $subcategory;
    }
}
