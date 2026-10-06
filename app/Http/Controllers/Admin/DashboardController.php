<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Redirect portal users away from admin dashboard
        if ($user && (
            in_array($user->domain, ['agent', 'associate_agent']) ||
            ($user->domain === 'system_user' && $user->hasAnyRole([
                'sales_officer', 'sales_supervisor', 'booking_officer', 'counter_staff'
            ]))
        )) {
            return redirect('/portal');
        }

        // Redirect admin users to the new Cargo dashboard
        return redirect()->route('admin.cargo.dashboard');
    }
}
