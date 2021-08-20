<?php

namespace App\Http\Controllers;

use App\SubscriptionPlan;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    /**
     * List plans.
     * GET /api/subscription-plans
     */
    public function index(Request $req)
    {
        $plans = SubscriptionPlan::latest();

        if ($query = $req->query('query', false)) {
            $plans->where('title', 'like', "%$query%");
        }

        if ($role = $req->query('role', false)) {
            $plans->where('role', $role);
        }

        if ($req->query('nopaginate', false) == '1') {
            $plans = $plans->get();
        } else {
            $plans = $plans->paginate($req->query('per_page', 20));
        }

        if ($plans->isEmpty()) return Utils::error('No plans found!', 404);
        return $plans;
    }

    /**
     * Create plan.
     * POST /api/subscription-plans
     */
    public function create(Request $req)
    {
        $this->validate($req, [
            'title' => 'required|string',
            'description' => 'nullable|string',
            'validity' => 'required|numeric',
            'price' => 'required|numeric',
            'role' => 'required|in:wholesaler,retailer',
        ]);

        // create plan
        $plan = new SubscriptionPlan;
        $plan->fill($req->all());
        $plan->save();

        return $plan;
    }

    /**
     * Show plan.
     * GET /api/subscription-plans/{id}
     */
    public function show(Request $req, $id)
    {
        $plan = SubscriptionPlan::find($id);
        if (empty($plan)) Utils::error('Subscription plan not found!', 404);
        return $plan;
    }

    /**
     * Update plan.
     * POST /api/subscription-plans/{id}
     */
    public function update(Request $req, $id)
    {
        $plan = SubscriptionPlan::find($id);
        if (empty($plan)) Utils::error('Subscription plan not found!', 404);

        $this->validate($req, [
            'title' => 'string',
            'description' => 'nullable|string',
            'validity' => 'numeric',
            'price' => 'numeric',
            'role' => 'in:wholesaler,retailer',
        ]);

        // update plan
        if ($req->has('title')) $plan->title = $req->input('title');
        if ($req->has('description')) $plan->description = $req->input('description');
        if ($req->has('validity')) $plan->validity = $req->input('validity');
        if ($req->has('price')) $plan->price = $req->input('price');
        if ($req->has('role')) $plan->role = $req->input('role');

        $plan->save();

        return $plan;
    }

    /**
     * Delete plan by id.
     *
     * DELETE /api/subscription-plans/{id}
     */
    public function delete(Request $req, $id)
    {
        $plan = SubscriptionPlan::find($id);
        if (empty($plan)) return Utils::error('Subscription plan not found!', 404);
        $plan->delete();
        return $plan;
    }
}
