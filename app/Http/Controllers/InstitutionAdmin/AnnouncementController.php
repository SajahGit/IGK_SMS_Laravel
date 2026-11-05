<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionAdmin\AnnouncementRequest;
use App\Models\Announcement;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Services\AnnouncementDeliveryService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::with(['creator', 'program', 'schoolClass'])
            ->orderByDesc('published_at')
            ->paginate(15);

        return view('InstitutionAdmin.InstitutionAdminAnnouncementsIndex', [
            'announcements' => $announcements,
            'programs' => Program::orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
        ]);
    }

    public function store(AnnouncementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $announcement = Announcement::create($data);
        AuditLogger::log('created', $announcement, null, $announcement->toArray());
        app(AnnouncementDeliveryService::class)->deliver($announcement);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement published successfully.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('InstitutionAdmin.InstitutionAdminAnnouncementsEdit', [
            'announcement' => $announcement,
            'programs' => Program::orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
        ]);
    }

    public function update(AnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $old = $announcement->toArray();
        $announcement->update($request->validated());
        $announcement->update(['notifications_sent_at' => null]);
        AuditLogger::log('updated', $announcement, $old, $announcement->toArray());
        app(AnnouncementDeliveryService::class)->deliver($announcement);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        AuditLogger::log('deleted', $announcement, $announcement->toArray(), null);
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement deleted.');
    }
}
