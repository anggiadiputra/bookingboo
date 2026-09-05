<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerFavoriteController extends Controller
{
    /**
     * Toggle caregiver favorit milik customer yang sedang login.
     */
    public function toggle(Request $request, Caregiver $caregiver): RedirectResponse
    {
        $customer = $request->user()->customer;

        if (! $customer instanceof Customer) {
            abort(403);
        }

        if ($customer->favoriteCaregivers()->where('caregivers.id', $caregiver->id)->exists()) {
            $customer->favoriteCaregivers()->detach($caregiver->id);
        } else {
            $customer->favoriteCaregivers()->syncWithoutDetaching([$caregiver->id]);
        }

        return back();
    }
}
