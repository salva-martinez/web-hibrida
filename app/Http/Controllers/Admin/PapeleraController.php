<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ejercicio;
use App\Models\Estimulo;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Papelera: todo lo que se ha desactivado (borrado lógico) y se puede recuperar.
 *
 * No existe borrado físico de cuentas ni de planes. Desactivar oculta el
 * registro de las listas activas y le impide al paciente entrar, pero conserva
 * intactos los datos clínicos y permite restaurarlo en cualquier momento.
 */
class PapeleraController extends Controller
{
    public function index()
    {
        $fisioId = Auth::id();

        $pacientes = User::onlyTrashed()
            ->where('role', 'paciente')
            ->deFisio($fisioId)
            ->get();

        $planes = Plan::onlyTrashed()
            ->deFisio($fisioId)
            ->with('paciente')
            ->orderBy('fecha', 'desc')
            ->get();

        $estimulos = Estimulo::onlyTrashed()
            ->deFisio($fisioId)
            ->orderBy('orden')
            ->get();

        $ejercicios = Ejercicio::onlyTrashed()
            ->deFisio($fisioId)
            ->with('estimulo')
            ->get();

        return view('admin.papelera', compact('pacientes', 'planes', 'estimulos', 'ejercicios'));
    }

    public function restorePaciente(int $id)
    {
        $paciente = User::onlyTrashed()
            ->where('role', 'paciente')
            ->deFisio(Auth::id())
            ->findOrFail($id);

        $paciente->restore();

        return redirect()->route('admin.papelera.index')
            ->with('success', 'Paciente restaurado correctamente.');
    }

    public function restorePlan(int $id)
    {
        $plan = Plan::onlyTrashed()
            ->deFisio(Auth::id())
            ->findOrFail($id);

        $plan->restore();

        // Se mantiene la regla de "un único plan activo por paciente": si el plan
        // restaurado estaba activo, se desactivan los demás.
        if ($plan->activo) {
            Plan::where('paciente_id', $plan->paciente_id)
                ->where('id', '!=', $plan->id)
                ->where('activo', true)
                ->update(['activo' => false]);
        }

        return redirect()->route('admin.papelera.index')
            ->with('success', 'Plan restaurado correctamente.');
    }

    public function restoreEstimulo(int $id)
    {
        $estimulo = Estimulo::onlyTrashed()
            ->deFisio(Auth::id())
            ->findOrFail($id);

        $estimulo->restore();

        // Los ejercicios que se desactivaron junto al estímulo vuelven con él.
        Ejercicio::onlyTrashed()
            ->where('estimulo_id', $estimulo->id)
            ->deFisio(Auth::id())
            ->get()
            ->each(fn ($ejercicio) => $ejercicio->restore());

        return redirect()->route('admin.papelera.index')
            ->with('success', 'Estímulo y sus ejercicios restaurados correctamente.');
    }

    public function restoreEjercicio(int $id)
    {
        $ejercicio = Ejercicio::onlyTrashed()
            ->deFisio(Auth::id())
            ->findOrFail($id);

        $ejercicio->restore();

        return redirect()->route('admin.papelera.index')
            ->with('success', 'Ejercicio restaurado correctamente.');
    }
}
