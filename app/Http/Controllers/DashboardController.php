<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CONTROLLER for the dashboard shown after login.
 * F05 (personal dashboard) will extend this with the user's tasks and deadlines.
 */
class DashboardController extends Controller
{
    /** GET /dashboard */
    public function index(Request $request): View
    {
        return view('dashboard', [
            'userName' => $request->user()->name,
        ]);
    }
}
