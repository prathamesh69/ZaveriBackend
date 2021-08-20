<?php

namespace App\Http\Controllers;

use App\WholesalerFirmImage;
use App\Helpers\ImageHelper;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WholesalerFirmImageController extends Controller
{
    /**
     * List images.
     * GET /api/wholesaler-firms/{firmId}/images
     */
    public function index(Request $req, $firmId)
    {
        $images = WholesalerFirmImage::where('wholesaler_firm_id', $firmId)->latest();

        if ($req->query('nopaginate', false) == '1') {
            $images = $images->get();
        } else {
            $images = $images->paginate($req->query('per_page', 20));
        }

        if ($images->isEmpty()) return Utils::error('No images found!', 404);
        return $images;
    }

    /**
     * Create image.
     * POST /api/wholesaler-firms/{firmId}/images
     */
    public function create(Request $req, $firmId)
    {
        $this->validate($req, [
            'image' => 'required|image',
        ]);

        // create image
        $image = new WholesalerFirmImage;
        $image->fill($req->all());
        $image->wholesaler_firm_id = $firmId;
        if ($req->hasFile('image')) {
            $image->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_WHOLESALER_FIRM_IMAGE);
        }
        $image->save();

        return $image;
    }

    /**
     * Show image.
     * GET /api/wholesaler-firms/{firmId}/images/{id}
     */
    public function show(Request $req, $firmId, $id)
    {
        $image = WholesalerFirmImage::where('wholesaler_firm_id', $firmId)->find($id);
        if (empty($image)) Utils::error('Image not found!', 404);
        return $image;
    }

    /**
     * Update image.
     * POST /api/wholesaler-firms/{firmId}/images/{id}
     */
    public function update(Request $req, $firmId, $id)
    {
        $image = WholesalerFirmImage::where('wholesaler_firm_id', $firmId)->find($id);
        if (empty($image)) Utils::error('Image not found!', 404);

        $this->validate($req, [
            'image' => 'image',
        ]);

        // update image
        if ($req->hasFile('image')) {
            if ($image->image != null) ImageHelper::delete(ImageHelper::$TYPE_WHOLESALER_FIRM_IMAGE, $image->image);
            $image->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_WHOLESALER_FIRM_IMAGE);
        }
        $image->save();

        return $image;
    }

    /**
     * Delete image by id.
     *
     * DELETE /api/wholesaler-firms/{firmId}/images/{id}
     */
    public function delete(Request $req, $firmId, $id)
    {
        $image = WholesalerFirmImage::where('wholesaler_firm_id', $firmId)->find($id);
        if (empty($image)) return Utils::error('Image not found!', 404);
        $image->delete();
        if ($image->image != null) ImageHelper::delete(ImageHelper::$TYPE_WHOLESALER_FIRM_IMAGE, $image->image);
        return $image;
    }
}
