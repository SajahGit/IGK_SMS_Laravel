<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionAdmin\StoreSettingRequest;
use App\Http\Requests\InstitutionAdmin\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Setting::class);

        $settings = Setting::orderBy('group')->orderBy('key')->get();

        return view('InstitutionAdmin.InstitutionAdminSettingsIndex', compact('settings'));
    }

    public function create(): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        return redirect()->route('admin.settings.index');
    }

    public function store(StoreSettingRequest $request): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        Setting::create($request->validated());

        return redirect()->route('admin.settings.index')
            ->with('success', 'Setting created successfully.');
    }

    public function edit(Setting $setting): RedirectResponse
    {
        $this->authorize('update', $setting);

        return redirect()->route('admin.settings.index');
    }

    public function update(UpdateSettingRequest $request, Setting $setting): RedirectResponse
    {
        $this->authorize('update', $setting);

        $setting->update($request->validated());

        return redirect()->route('admin.settings.index')
            ->with('success', 'Setting updated successfully.');
    }

    public function destroy(Setting $setting): RedirectResponse
    {
        $this->authorize('update', $setting);

        $setting->delete();

        return redirect()->route('admin.settings.index')
            ->with('success', 'Setting deleted.');
    }
}
