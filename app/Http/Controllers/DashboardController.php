<?php

namespace App\Http\Controllers;

use App\Domain\Front\Services\FrontIntegrationService;
use App\Http\Resources\SelectionProcessResource;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $selection = $request->user()?->currentSelectionProcess
            ?? SelectionProcess::latest('year')->latest('id')->first();
        if (! $selection) {
            return to_route('selection.create');
        }

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return Inertia::render('Dashboard', [
            'selection' => new SelectionProcessResource($selection),
            'dashboard' => FrontIntegrationService::dashboardStats($selection, $user),
        ]);
    }
}
