<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchContextController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['school_id' => ['required', 'integer']]);
        $school = $request->user()->schoolAccesses()->whereKey($data['school_id'])->firstOrFail();
        $request->session()->put('active_school_id', $school->id);

        return redirect()->route($request->user()->portalHomeRoute())->with('status', 'Now managing '.$school->name.'.');
    }
}
