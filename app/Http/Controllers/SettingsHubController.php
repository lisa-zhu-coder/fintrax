<?php

namespace App\Http\Controllers;

use App\Support\SettingsNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsHubController extends Controller
{
    public function show(string $group): View|RedirectResponse
    {
        if (! array_key_exists($group, SettingsNavigation::groupMeta())) {
            abort(404);
        }

        if (! SettingsNavigation::isGroupVisible($group)) {
            abort(404);
        }

        $meta = SettingsNavigation::groupMeta()[$group];
        $tabs = SettingsNavigation::tabsFor($group);

        if ($tabs->isNotEmpty()) {
            return redirect($tabs->first()['href']);
        }

        return view('settings.hub', [
            'group' => $group,
            'title' => $meta['label'],
        ]);
    }
}
