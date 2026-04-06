<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserLocaleController extends Controller
{
    public function update(Request $request)
    {
        $available = array_keys(config('app.available_locales', ['en' => 'English']));

        $request->validate([
            'locale' => ['required', 'string', 'in:' . implode(',', $available)],
        ]);

        $request->user()->update(['locale' => $request->locale]);

        return back()->with('status', 'locale-updated');
    }
}
