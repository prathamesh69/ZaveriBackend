<?php

namespace App\Http\Controllers;

use App\WholesalerFirm;
use App\Helpers\ImageHelper;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Product;
use Illuminate\Http\Request;

class WholesalerFirmController extends Controller
{
    /**
     * List firms.
     * GET /api/firms
     */
    public function index(Request $req)
    {
        $firms = WholesalerFirm::latest();

        if ($query = $req->query('query', false)) {
            $firms->whereRaw('(name LIKE ? OR mobile LIKE ?)', ["%$query%", "%$query%"]);
        }

        if ($city_id = $req->query('city_id', false)) {
            $firms->where('city_id', $city_id);
        }

        if ($req->query('nopaginate', false) == '1') {
            $firms = $firms->get();
        } else {
            $firms = $firms->paginate($req->query('per_page', 20));
        }

        if ($firms->isEmpty()) return Utils::error('No firms found!', 404);
        return $firms;
    }

    /**
     * Create firm.
     * POST /api/firms
     */
    public function create(Request $req)
    {
        $this->validate($req, [
            'name' => 'required',
            'mobile' => 'required|size:10',
            'image' => 'nullable|image',
            'address' => 'nullable|string',
            'gst' => 'nullable|string|size:15',
            'marks' => 'nullable|string',
            'meltings' => 'nullable|string',
            'pincode' => 'required|string|size:6',
            'city_id' => 'required|exists:cities,id',
        ]);

        // create firm
        $firm = new WholesalerFirm;
        $firm->fill($req->all());
        $firm->preferences = json_encode(['auto_accept_followers' => false]);
        if ($req->hasFile('image')) {
            $firm->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_WHOLESALER_FIRM);
        }
        $firm->save();

        $firm->qr_code = ImageHelper::generateQR(json_encode(['id' => $firm->id, 'role' => 'wholesaler_firm']));
        $firm->save();

        return $firm;
    }

    /**
     * Show firm.
     * GET /api/firms/{id}
     */
    public function show(Request $req, $id)
    {
        $firm = WholesalerFirm::find($id);
        if (empty($firm)) Utils::error('Firm not found!', 404);
        return $firm;
    }

    /**
     * Update firm.
     * POST /api/firms/{id}
     */
    public function update(Request $req, $id)
    {
        $firm = WholesalerFirm::find($id);
        if (empty($firm)) Utils::error('Firm not found!', 404);

        $this->validate($req, [
            'name' => 'nullable|string',
            'mobile' => 'nullable|size:10',
            'image' => 'nullable|image',
            'address' => 'nullable|string',
            'gst' => 'nullable|string|size:15',
            'marks' => 'nullable|string',
            'meltings' => 'nullable|string',
            'pincode' => 'nullable|string|size:6',
            'city_id' => 'nullable|exists:cities,id',
            'preferences' => 'nullable|string',
            'email_addresses' => 'nullable|json',
            'icom_numbers' => 'nullable|json',
            'landline_numbers' => 'nullable|json',
            'links' => 'nullable|json',
        ]);

        // update firm
        if ($req->has('name')) $firm->name = $req->input('name');
        if ($req->has('mobile')) $firm->mobile = $req->input('mobile');
        if ($req->has('address')) $firm->address = $req->input('address');
        if ($req->has('gst')) $firm->gst = $req->input('gst');
        if ($req->has('marks')) $firm->marks = $req->input('marks');
        if ($req->has('meltings')) $firm->meltings = $req->input('meltings');
        if ($req->has('pincode')) $firm->pincode = $req->input('pincode');
        if ($req->has('city_id')) $firm->city_id = $req->input('city_id');
        if ($req->has('preferences')) $firm->preferences = $req->input('preferences');

        if ($req->has('email_addresses')) $firm->email_addresses = $req->input('email_addresses');
        if ($req->has('icom_numbers')) $firm->icom_numbers = $req->input('icom_numbers');
        if ($req->has('landline_numbers')) $firm->landline_numbers = $req->input('landline_numbers');
        if ($req->has('links')) $firm->links = $req->input('links');

        if ($req->hasFile('image')) {
            if ($firm->image != null) ImageHelper::delete(ImageHelper::$TYPE_WHOLESALER_FIRM, $firm->image);
            $firm->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_WHOLESALER_FIRM);
        }
        $firm->save();

        return $firm;
    }

    /**
     * Delete firm by id.
     *
     * DELETE /api/firms/{id}
     */
    public function delete(Request $req, $id)
    {
        $firm = WholesalerFirm::find($id);
        if (empty($firm)) return Utils::error('Firm not found!', 404);
        $firm->delete();
        if ($firm->image != null) ImageHelper::delete(ImageHelper::$TYPE_WHOLESALER_FIRM, $firm->image);
        return $firm;
    }

    /**
     * Show firm's public profile.
     * GET /api/firms/{id}/profile
     */
    public function showPublic(Request $req, $id)
    {
        $firm = WholesalerFirm::find($id);
        if (empty($firm)) Utils::error('Firm not found!', 404);

        $firm->products = Product::where('wholesaler_firm_id', $id)
            ->orderByRaw('category_id DESC, created_at DESC')
            ->get();
        return $firm;
    }
}
