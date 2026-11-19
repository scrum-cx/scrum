<?php

namespace App\Http\ViewComposers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CONTROLLER-LAYER helper for the shared layout (resources/views/layouts).
 *
 * Every page uses the same layout, so instead of each controller passing the
 * navigation data, this class prepares it once per request and hands it to the
 * layout views. The views only display these values.
 * Registered in App\Providers\AppServiceProvider::boot().
 */
class LayoutComposer
{
    /** Links shown in the top navigation: label => [route name, route pattern for "active"]. */
    private const NAV_LINKS = [
        'Dashboard' => ['dashboard', 'dashboard'],
    ];

    public function __construct(private Request $request) {}

    /** Data for layouts/app.blade.php */
    public function app(View $view): void
    {
        $view->with('successMessage', $this->request->session()->get('success'));
    }

    /** Data for layouts/navigation.blade.php */
    public function navigation(View $view): void
    {
        $user = $this->request->user();

        $view->with([
            'navUser' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->label(),
            ],
            'navLinks' => collect(self::NAV_LINKS)->map(fn (array $link, string $label) => [
                'label' => $label,
                'url' => route($link[0]),
                'active' => $this->request->routeIs($link[1]),
            ])->values()->all(),
        ]);
    }
}
