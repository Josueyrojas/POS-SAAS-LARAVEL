<?php

namespace App\Http\Controllers\Pos;

use App\Enums\CashSessionStatus;
use App\Http\Controllers\Controller;
use App\Models\CashSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashSessionController extends Controller
{
    public function create()
    {
        $open = $this->openSessionForCurrentUser();
        if ($open) {
            return redirect()->route('pos.cash-sessions.show');
        }

        return view('pos.cash-sessions.create');
    }

    public function store(Request $request)
    {
        if ($this->openSessionForCurrentUser()) {
            return redirect()->route('pos.cash-sessions.show');
        }

        $data = $request->validate(['opening_amount' => ['required', 'numeric', 'min:0']]);

        CashSession::create([
            'user_id' => Auth::id(),
            'status' => CashSessionStatus::OPEN,
            'opening_amount' => $data['opening_amount'],
            'opening_at' => now(),
        ]);

        return redirect()->route('pos.sales.create')->with('status', 'Turno abierto.');
    }

    public function show()
    {
        $session = $this->openSessionForCurrentUser();
        if (! $session) {
            return redirect()->route('pos.cash-sessions.create');
        }

        return view('pos.cash-sessions.show', [
            'session' => $session,
            'expected' => $session->cashExpected(),
        ]);
    }

    public function close(Request $request)
    {
        $session = $this->openSessionForCurrentUser();
        if (! $session) {
            return redirect()->route('pos.cash-sessions.create');
        }

        $data = $request->validate(['closing_amount' => ['required', 'numeric', 'min:0']]);

        $expected = $session->cashExpected();
        $session->update([
            'status' => CashSessionStatus::CLOSED,
            'closing_amount' => $data['closing_amount'],
            'expected_amount' => $expected,
            'difference' => round($data['closing_amount'] - $expected, 2),
            'closed_at' => now(),
        ]);

        return redirect()->route('pos.dashboard')->with('status', 'Turno cerrado.');
    }

    /** Historial de turnos de todo el negocio (solo admin). */
    public function index()
    {
        $sessions = CashSession::with('user')->latest('opening_at')->get();

        return view('pos.cash-sessions.index', compact('sessions'));
    }

    private function openSessionForCurrentUser(): ?CashSession
    {
        return CashSession::where('user_id', Auth::id())
            ->where('status', CashSessionStatus::OPEN->value)
            ->first();
    }
}
