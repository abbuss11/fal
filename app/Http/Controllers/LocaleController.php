<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $supportedLocales = config('app.supported_locales', ['fr', 'en']);
        abort_unless(in_array($locale, $supportedLocales, true), 404);

        $request->session()->put('locale', $locale);

        return back();
    }
}
