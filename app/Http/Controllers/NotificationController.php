<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('Notifications.Index', compact('notifications'));
    }

    public function show(DatabaseNotification $notification): View
    {
        $user = auth()->user();
        $ownedNotification = $user->notifications()->findOrFail($notification->id);
        if (! $ownedNotification->read_at) {
            $ownedNotification->markAsRead();
        }

        return view('Notifications.Show', [
            'notification' => $ownedNotification,
            'actionUrl' => $this->resolveActionUrl($user, $ownedNotification),
        ]);
    }

    public function read(DatabaseNotification $notification): RedirectResponse
    {
        $ownedNotification = auth()->user()->notifications()->findOrFail($notification->id);
        $ownedNotification->markAsRead();

        return redirect()->route('notifications.show', $ownedNotification);
    }

    public function readAll(): RedirectResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    private function resolveActionUrl(User $user, DatabaseNotification $notification): ?string
    {
        $rawUrl = (string) ($notification->data['url'] ?? '');
        $parsedPath = parse_url($rawUrl, PHP_URL_PATH) ?: '';
        $parsedQuery = parse_url($rawUrl, PHP_URL_QUERY);

        if ($parsedPath !== '' && $parsedPath !== '/') {
            $relative = $parsedPath.($parsedQuery ? '?'.$parsedQuery : '');

            return url($relative);
        }

        $type = class_basename((string) $notification->type);
        $isStudent = $user->isStudent();
        $isGuardian = in_array(strtolower((string) $user->role), ['parent', 'guardian'], true);
        $isTeacher = $user->isTeacher();
        $isAdmin = $user->isAdmin();

        $routeName = match ($type) {
            'InvoiceIssuedNotification', 'InvoiceOverdueNotification', 'PaymentReceivedNotification' => match (true) {
                $isStudent => 'student.invoices',
                $isGuardian => 'guardian.invoices',
                $isAdmin => 'admin.invoices.index',
                strtolower((string) $user->role) === 'accountant' => 'accountant.invoices.index',
                default => 'dashboard',
            },
            'TermResultsPublished' => match (true) {
                $isStudent => 'student.results',
                $isGuardian => 'guardian.dashboard',
                $isTeacher => 'teacher.exams.index',
                $isAdmin => 'admin.term-results.index',
                default => 'dashboard',
            },
            'SchoolMessageReceived' => 'messages.index',
            'LibraryLoanOverdueNotification' => ($isAdmin || strtolower((string) $user->role) === 'librarian') ? 'library.loans.index' : 'dashboard',
            'LowStockNotification' => $isAdmin ? 'admin.inventory.index' : 'dashboard',
            'AnnouncementPublished' => $isAdmin ? 'admin.announcements.index' : 'dashboard',
            'LearningMaterialPublishedNotification', 'LearningMaterialAccessNotification' => 'learning-materials.library',
            default => null,
        };

        if ($routeName && Route::has($routeName)) {
            return route($routeName);
        }

        return null;
    }
}
