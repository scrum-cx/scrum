<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CONTROLLER for the public landing page.
 */
class HomeController extends Controller
{
    /** GET / — signed-in users go straight to their dashboard. */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return view('welcome');
    }
}
