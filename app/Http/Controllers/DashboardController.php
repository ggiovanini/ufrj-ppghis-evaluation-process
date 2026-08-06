<?php

namespace App\Http\Controllers;

use App\Http\Resources\SelectionProcessResource;
use App\Models\SelectionProcess;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $selections = SelectionProcess::first();
        if (! $selections) {
            return to_route('selection.create');
        }

        return Inertia::render('Dashboard', [
            'selection' => new SelectionProcessResource($selections),
        ]);
    }
}
