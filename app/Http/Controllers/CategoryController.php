<?php

namespace App\Http\Controllers;

use App\Category;
use App\Helpers\ImageHelper;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List categories.
     * GET /api/categories
     */
    public function index(Request $req)
    {
        $categories = Category::latest();

        if ($query = $req->query('query', false)) {
            $categories->where('name', 'like', "%$query%");
        }

        if ($req->query('nopaginate', false) == '1') {
            $categories = $categories->get();
        } else {
            $categories = $categories->paginate($req->query('per_page', 20));
        }

        if ($categories->isEmpty()) return Utils::error('No categories found!', 404);
        return $categories;
    }

    /**
     * Create category.
     * POST /api/categories
     */
    public function create(Request $req)
    {
        $this->validate($req, [
            'name' => 'required',
            'image' => 'nullable|image',
        ]);

        // create category
        $category = new Category;
        $category->fill($req->all());
        if ($req->hasFile('image')) {
            $category->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_CATEGORY);
        }
        $category->save();

        return $category;
    }

    /**
     * Show category.
     * GET /api/categories/{id}
     */
    public function show(Request $req, $id)
    {
        $category = Category::find($id);
        if (empty($category)) Utils::error('Category not found!', 404);
        return $category;
    }

    /**
     * Update category.
     * POST /api/categories/{id}
     */
    public function update(Request $req, $id)
    {
        $category = Category::find($id);
        if (empty($category)) Utils::error('Category not found!', 404);

        $this->validate($req, [
            'image' => 'nullable|image',
        ]);

        // update category
        if ($req->has('name')) $category->name = $req->input('name');
        if ($req->hasFile('image')) {
            if ($category->image != null) ImageHelper::delete(ImageHelper::$TYPE_CATEGORY, $category->image);
            $category->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_CATEGORY);
        }
        $category->save();

        return $category;
    }

    /**
     * Delete category by id.
     *
     * DELETE /api/categories/{id}
     */
    public function delete(Request $req, $id)
    {
        $category = Category::find($id);
        if (empty($category)) return Utils::error('Category not found!', 404);
        $category->delete();
        if ($category->image != null) ImageHelper::delete(ImageHelper::$TYPE_CATEGORY, $category->image);
        return $category;
    }
}
