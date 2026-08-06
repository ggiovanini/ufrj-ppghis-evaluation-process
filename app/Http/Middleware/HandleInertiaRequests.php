<?php

namespace App\Http\Middleware;

use App\Models\SelectionProcess;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $currentSelectionProcess = null;

        if ($user) {
            $currentSelectionProcess = $user->currentSelectionProcess;

            if (! $currentSelectionProcess) {
                $currentSelectionProcess = SelectionProcess::latest('year')->latest('id')->first();
                if ($currentSelectionProcess) {
                    $user->update(['current_selection_process_id' => $currentSelectionProcess->id]);
                    $user->refresh();
                    $currentSelectionProcess = $user->currentSelectionProcess;
                }
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'roles' => $user ? $user->getRoleNames() : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
                'currentSelectionProcess' => $currentSelectionProcess?->load('reviewForm'),
                'is_impersonating' => $request->session()->has('impersonated_by'),
            ],
            'selectionProcesses' => SelectionProcess::orderByDesc('year')->orderByDesc('id')->get(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
