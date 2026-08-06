<?php

namespace App\Http\Controllers\SelectionProcess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserSelectionController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'selection_process_id' => ['required', 'exists:selection_processes,id'],
        ]);

        $request->user()->update([
            'current_selection_process_id' => $validated['selection_process_id'],
        ]);

        return back();
    }
}
