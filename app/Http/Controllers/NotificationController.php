<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $this->autorizarCliente($request);

        $notificaciones = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notificaciones.index', compact('notificaciones'));
    }

    public function leer(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $this->autorizarCliente($request);
        abort_unless(
            $notification->notifiable_type === User::class
            && (int) $notification->notifiable_id === $request->user()->id,
            403
        );

        $notification->markAsRead();

        return redirect()->route('reservas.mis');
    }

    public function leerTodas(Request $request): RedirectResponse
    {
        $this->autorizarCliente($request);
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('notificaciones.index')
            ->with('status', 'Notificaciones marcadas como leídas.');
    }

    private function autorizarCliente(Request $request): void
    {
        abort_unless($request->user()?->rol === User::ROL_CLIENTE, 403);
    }
}
