<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Helpers\ImageHelper;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * List brands.
     * GET /api/brands
     */
    public function index(Request $req)
    {
        $brands = Brand::latest();

        if ($query = $req->query('query', false)) {
            $brands->where('name', 'like', "%$query%");
        }

        if ($req->query('nopaginate', false) == '1') {
            $brands = $brands->get();
        } else {
            $brands = $brands->paginate($req->query('per_page', 20));
        }

        if ($brands->isEmpty()) return Utils::error('No brands found!', 404);
        return $brands;
    }

    /**
     * Create brand.
     * POST /api/brands
     */
    public function create(Request $req)
    {
        $this->validate($req, [
            'name' => 'required',
            'image' => 'nullable|image',
        ]);

        // create brand
        $brand = new Brand;
        $brand->fill($req->all());
        if ($req->hasFile('image')) {
            $brand->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_BRAND);
        }
        $brand->save();

        return $brand;
    }

    /**
     * Show brand.
     * GET /api/brands/{id}
     */
    public function show(Request $req, $id)
    {
        $brand = Brand::find($id);
        if (empty($brand)) Utils::error('Brand not found!', 404);
        return $brand;
    }

    /**
     * Update brand.
     * POST /api/brands/{id}
     */
    public function update(Request $req, $id)
    {
        $brand = Brand::find($id);
        if (empty($brand)) Utils::error('Brand not found!', 404);

        $this->validate($req, [
            'image' => 'nullable|image',
        ]);

        // update brand
        if ($req->has('name')) $brand->name = $req->input('name');
        if ($req->hasFile('image')) {
            if ($brand->image != null) ImageHelper::delete(ImageHelper::$TYPE_BRAND, $brand->image);
            $brand->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_BRAND);
        }
        $brand->save();

        return $brand;
    }

    /**
     * Delete brand by id.
     *
     * DELETE /api/brands/{id}
     */
    public function delete(Request $req, $id)
    {
        $brand = Brand::find($id);
        if (empty($brand)) return Utils::error('Brand not found!', 404);
        $brand->delete();
        if ($brand->image != null) ImageHelper::delete(ImageHelper::$TYPE_BRAND, $brand->image);
        return $brand;
    }
}
