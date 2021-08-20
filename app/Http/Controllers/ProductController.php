<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 120);

use App\Category;
use App\City;
use App\Helpers\Auth;
use App\Product;
use App\Helpers\ImageHelper;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\ProductBookmark;
use App\ProductTag;
use App\Subcategory;
use App\WholesalerFirm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * List products.
     * GET /api/products
     */
    public function index(Request $req)
    {
        $products = Product::select('*');

        if ($whats_new = $req->query('whats_new', false)) {
            if ($whats_new == '1') {
                $products->whereRaw('CAST(created_at AS DATE) > DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
            }
        }

        if ($query = $req->query('query', false)) {
            $products->where('tags', 'like', "%$query%");
        }

        if (Auth::isWholesaler()) {
            $products->where('wholesaler_firm_id', Auth::user()->wholesaler_firm_id);
        } else if ($wholesaler_firm_id = $req->query('wholesaler_firm_id', false)) {
            $products->where('wholesaler_firm_id', $wholesaler_firm_id);
        }

        if ($category_id = $req->query('category_id', false)) {
            $products->whereIn('category_id', explode(',', $category_id));
        }

        if (($bookmarked = $req->query('bookmarked', false)) !== false) {
            if ($bookmarked == '1') {
                $products->whereRaw('id IN (SELECT DISTINCT product_id FROM product_bookmarks WHERE user_id = ?)', [Auth::id()]);
            } else {
                $products->whereRaw('id NOT IN (SELECT DISTINCT product_id FROM product_bookmarks WHERE user_id = ?)', [Auth::id()]);
            }
        }

        if ($city_id = $req->query('city_id', false)) {
            $ids = explode(',', $city_id);
            $bindings = implode(',', array_fill(0, count($ids), '?'));

            $products->whereRaw("wholesaler_firm_id IN (SELECT id FROM wholesaler_firms wf WHERE wf.city_id IN ($bindings))", array_values($ids));
        }

        if ($subcategory_id = $req->query('subcategory_id', false)) {
            $products->whereRaw('id IN (SELECT product_id FROM product_subcategories WHERE subcategory_id = ?)', [$subcategory_id]);
        }

        if ($brand_id = $req->query('brand_id', false)) {
            $products->whereIn('brand_id', explode(',', $brand_id));
        }

        if ($type_id = $req->query('type_id', false)) {
            $products->whereIn('type_id', explode(',', $type_id));
        }

        if ($melting = $req->query('melting', false)) {
            $products->whereIn('melting', explode(',', $melting));
        }

        if (($weight_from = $req->query('weight_from', false)) && ($weight_to = $req->query('weight_to', false))) {
            $products->whereRaw(
                '((weight_from <= ? AND weight_to >= ?) OR (weight_from <= ? AND weight_to >= ?) OR (weight_from <= ? AND weight_to >= ?) OR (weight_from >= ? AND weight_to <= ?))',
                [$weight_from, $weight_from, $weight_to, $weight_to, $weight_from, $weight_to, $weight_from, $weight_to]
            );
        } else if ($weight_from = $req->query('weight_from', false)) {
            $products->where('weight_from', '<=', $weight_from);
            $products->where('weight_to', '>=', $weight_from);
        } else if ($weight_to = $req->query('weight_to', false)) {
            $products->where('weight_from', '<=', $weight_to);
            $products->where('weight_to', '>=', $weight_to);
        }

        // if (Auth::isRetailer()) {
        //     $products->orderBy('priority', 'DESC');
        // } else {
        $products->latest();
        // }

        if ($req->query('nopaginate', false) == '1') {
            $products = $products->get();
        } else {
            $products = $products->paginate($req->query('per_page', 20));
        }

        if ($products->isEmpty()) return Utils::error('No products found!', 404);
        return $products;
    }

    /**
     * Create product.
     * POST /api/products
     */
    public function create(Request $req)
    {
        if (Auth::isWholesaler()) $req->merge(['wholesaler_firm_id' => Auth::user()->wholesaler_firm_id]);

        $this->validate($req, [
            'wholesaler_firm_id' => 'required|exists:wholesaler_firms,id',
            'category_id' => 'required|exists:categories,id',
            'subcategory_ids' => 'nullable|json',
            'brand_id' => 'nullable|exists:brands,id',
            'type_id' => 'nullable|exists:product_types,id',
            'melting' => 'nullable',
            'weight_from' => 'nullable|numeric',
            'weight_to' => 'nullable|numeric',
            'tags' => 'nullable',
            'mark' => 'nullable',
            'image' => 'required|image',
        ]);

        // create product
        $product = new Product;
        $product->fill($req->all());
        if ($req->hasFile('image')) {
            $product->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_PRODUCT);
        }

        // Process product tags
        $tags = collect(explode(',', $product->tags))
            ->map(function ($tag) {
                return trim($tag);
            })
            ->reject(function ($tag) {
                return empty($tag);
            });

        if (!empty($product->mark) && !$tags->contains($product->mark)) {
            $tags->push($product->mark);
        }

        if ($tags->count() > 0) {
            $product->tags = $tags->join(',');
        }

        $product->save();

        // Link product subcategories
        $subcategory_ids = json_decode($req->input('subcategory_ids'), 'true');
        $product->updateSubcategories($subcategory_ids);

        return $product;
    }

    /**
     * Show product.
     * GET /api/products/{id}
     */
    public function show(Request $req, $id)
    {
        $product = Product::find($id);
        if (empty($product)) Utils::error('Product not found!', 404);
        return $product;
    }

    /**
     * Update product.
     * POST /api/products/{id}
     */
    public function update(Request $req, $id)
    {
        $product = Product::find($id);
        if (empty($product)) Utils::error('Product not found!', 404);

        $this->validate($req, [
            'wholesaler_firm_id' => 'exists:wholesaler_firms,id',
            'category_id' => 'exists:categories,id',
            'subcategory_ids' => 'nullable|json',
            'brand_id' => 'nullable|exists:brands,id',
            'type_id' => 'nullable|exists:product_types,id',
            'melting' => 'nullable',
            'weight_from' => 'nullable|numeric',
            'weight_to' => 'nullable|numeric',
            'tags' => 'nullable',
            'mark' => 'nullable',
            'image' => 'image',
        ]);

        // update product
        if ($req->has('wholesaler_firm_id')) $product->wholesaler_firm_id = $req->input('wholesaler_firm_id');
        if ($req->has('category_id')) $product->category_id = $req->input('category_id');
        if ($req->has('brand_id')) $product->brand_id = $req->input('brand_id');
        if ($req->has('type_id')) $product->type_id = $req->input('type_id');
        if ($req->has('melting')) $product->melting = $req->input('melting');
        if ($req->has('weight_from')) $product->weight_from = $req->input('weight_from');
        if ($req->has('weight_to')) $product->weight_to = $req->input('weight_to');
        if ($req->has('tags')) $product->tags = $req->input('tags');
        if ($req->has('mark')) $product->mark = $req->input('mark');
        if ($req->hasFile('image')) {
            if ($product->image != null) ImageHelper::delete(ImageHelper::$TYPE_PRODUCT, $product->image);
            $product->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_PRODUCT);
        }
        $product->save();

        if ($req->has('subcategory_ids')) {
            $subcategory_ids = json_decode($req->input('subcategory_ids'), 'true');
            $product->updateSubcategories($subcategory_ids);
        }

        return $product;
    }

    /**
     * Delete product by id.
     *
     * DELETE /api/products/{id}
     */
    public function delete(Request $req, $id)
    {
        $product = Product::find($id);
        if (empty($product)) return Utils::error('Product not found!', 404);
        $product->delete();
        if ($product->image != null) ImageHelper::delete(ImageHelper::$TYPE_PRODUCT, $product->image);
        return $product;
    }

    /**
     * List product meltings.
     * GET /api/products/meltings
     */
    public function meltings(Request $req)
    {
        $meltings = Product::whereNotNull('melting')->distinct('melting')->orderBy('melting')->pluck('melting');

        if ($meltings->isEmpty()) return Utils::error('No meltings found!', 404);
        return $meltings;
    }

    /**
     * List product weight range.
     * GET /api/products/weight-range
     */
    public function weightRange(Request $req)
    {
        return [
            'lower' => Product::min('weight_from') ?? 0,
            'upper' => Product::max('weight_to') ?? 0
        ];
    }

    /**
     * List product tags.
     * GET /api/products/tags
     */
    public function tags(Request $req)
    {
        $tags = ProductTag::distinct();

        if ($category_id = $req->query('category_id', false)) {
            $tags->whereIn('category_id', explode(',', $category_id));
        }

        $tags = $tags->pluck('tag');

        if ($tags->isEmpty()) return Utils::error('No tags found!', 404);
        return $tags;
    }

    /**
     * List products categorywise.
     * GET /api/products/categorywise
     */
    public function categorywise(Request $req)
    {
        // For wholesaler role
        if (Auth::isWholesaler()) {
            $categories = Category::whereRaw(
                '(SELECT COUNT(*) FROM products WHERE category_id = categories.id AND wholesaler_firm_id = ?) > 0',
                [Auth::user()->wholesaler_firm_id]
            )->get();

            $categories = $categories->map(function ($category) use ($req) {
                $category->count = DB::table('products')->selectRaw(
                    'COUNT(*) AS total, COUNT(IF( wholesaler_firm_id = ?, 1, NULL )) AS wholesaler',
                    [Auth::user()->wholesaler_firm_id]
                )
                    ->where('category_id', $category->id)
                    ->first();

                $product = Product::where('category_id', $category->id)->latest()
                    ->where('wholesaler_firm_id', Auth::user()->wholesaler_firm_id)
                    ->first();

                $category->product_thumb_url = !empty($product) ? $product->thumb_url : null;
                $category->products = [];
                return $category;
            })->reject(function ($category, $key) {
                return $category->count->wholesaler == 0;
            });

            if ($categories->isEmpty()) Utils::error('No products found!', 404);
            return $categories->values();
        }

        // For other roles
        else {
            $bookmarked = $req->query('bookmarked', false);


            $whatNewQuery = '';
            if ($whats_new = $req->query('whats_new', false)) {
                if ($whats_new == '1') {
                    $whatNewQuery = ' AND CAST(created_at AS DATE) > DATE_SUB(CURDATE(), INTERVAL 1 DAY) ';
                }
            }


            if ($bookmarked === false) {
                $categories = Category::whereRaw(
                    "(SELECT COUNT(*) FROM products WHERE category_id = categories.id $whatNewQuery)"
                )->get();
            } else if ($bookmarked == '1') {
                $categories = Category::whereRaw(
                    "(SELECT COUNT(*) FROM products WHERE category_id = categories.id $whatNewQuery AND products.id IN (SELECT DISTINCT product_id FROM product_bookmarks WHERE user_id = ?) > 0)",
                    [Auth::id()]
                )->get();
            } else {
                $categories = Category::whereRaw(
                    "(SELECT COUNT(*) FROM products WHERE category_id = categories.id $whatNewQuery AND products.id NOT IN (SELECT DISTINCT product_id FROM product_bookmarks WHERE user_id = ?) > 0)",
                    [Auth::id()]
                )->get();
            }


            $categories = $categories->map(function ($category) use ($req) {
                $product = Product::where('category_id', $category->id)->latest()
                    ->first();

                $category->product_thumb_url = !empty($product) ? $product->thumb_url : null;
                $category->products = [];
                return $category;
            });

            if ($categories->isEmpty()) Utils::error('No products found!', 404);
            return $categories->values();
        }
    }

    /**
     * List products categorywise.
     * GET /api/products/subcategorywise/{categoryId}
     */
    public function subcategorywise(Request $req, $categoryId)
    {
        $subcategories = Subcategory::whereRaw('(SELECT COUNT(*) FROM product_subcategories WHERE subcategory_id = subcategories.id) > 0')
            ->where('category_id', $categoryId)->get();

        $subcategories = $subcategories->map(function ($subcategory) use ($req) {
            $products = Product::whereRaw('id IN (SELECT product_id FROM product_subcategories WHERE subcategory_id = ?)', [$subcategory->id])->latest();

            if (Auth::isWholesaler()) {
                $products->where('wholesaler_firm_id', Auth::user()->wholesaler_firm_id);
            } else if ($wholesaler_firm_id = $req->query('wholesaler_firm_id', false)) {
                $products->where('wholesaler_firm_id', $wholesaler_firm_id);
            }

            if ($query = $req->query('query', false)) {
                $products->where('tags', 'like', "%$query%");
            }

            if ($brand_id = $req->query('brand_id', false)) {
                $products->whereIn('brand_id', explode(',', $brand_id));
            }

            if ($type_id = $req->query('type_id', false)) {
                $products->whereIn('type_id', explode(',', $type_id));
            }

            if ($melting = $req->query('melting', false)) {
                $products->whereIn('melting', explode(',', $melting));
            }

            if (($weight_from = $req->query('weight_from', false)) && ($weight_to = $req->query('weight_to', false))) {
                $products->whereRaw('((weight_from <= ? AND weight_to >= ?) OR (weight_from <= ? AND weight_to >= ?))', [$weight_from, $weight_from, $weight_to, $weight_to]);
            } else if ($weight_from = $req->query('weight_from', false)) {
                $products->where('weight_from', '<=', $weight_from);
                $products->where('weight_to', '>=', $weight_from);
            } else if ($weight_to = $req->query('weight_to', false)) {
                $products->where('weight_from', '<=', $weight_to);
                $products->where('weight_to', '>=', $weight_to);
            }

            $subcategory->products = $products->limit(10)->get();
            return $subcategory;
        })->reject(function ($subcategory, $key) {
            return $subcategory->products->isEmpty();
        });

        if ($subcategories->isEmpty()) Utils::error('No products found!', 404);
        return $subcategories->values();
    }

    /**
     * Show product to public.
     * GET /api/products/{id}/public
     */
    public function showPublic(Request $req, $id)
    {
        $product = Product::find($id);
        if (empty($product)) Utils::error('Product not found!', 404);
        return [
            'image_url' => $product->image_url
        ];
    }

    /**
     * List cities.
     * GET /api/products/cities
     */
    public function cities(Request $req)
    {
        if ($category_id = $req->query('category_id', false)) {
            $firmWhere = "id IN (SELECT DISTINCT wholesaler_firm_id FROM products WHERE category_id = '$category_id')";
        } else {
            $firmWhere = 'id IN (SELECT DISTINCT wholesaler_firm_id FROM products)';
        }

        $cities = City::whereIn(
            'id',
            WholesalerFirm::distinct()
                ->whereRaw($firmWhere)
                ->pluck('city_id')
        )->orderBy('name')->get();

        if ($cities->isEmpty()) Utils::error('No cities found!', 404);
        return $cities;
    }

    /**
     * Toggle product bookmark.
     * POST /api/products/{id}/bookmark/{status}
     */
    public function updateBookmark(Request $req, $id, $status)
    {
        if (!in_array($status, ['on', 'off'])) {
            Utils::error('Invalid status selected!', 400);
        }

        $bookmark = ProductBookmark::where('user_id', Auth::id())->where('product_id', $id)->first();
        if ($status == 'on' && empty($bookmark)) {
            $bookmark = new ProductBookmark();
            $bookmark->user_id = Auth::id();
            $bookmark->product_id = $id;
            $bookmark->save();

            return ['message' => 'success'];
        }

        if ($status == 'off' && !empty($bookmark)) {
            $bookmark->delete();
            return ['message' => 'success'];
        }

        return ['message' => 'success'];;
    }
}
