<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estimulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EstimuloController extends Controller
{
    public function index()
    {
        // El aislamiento global limita la lista a los estímulos de este fisio.
        $estimulos = Estimulo::orderBy('orden')->withCount('ejercicios')->get();
        return view('admin.estimulos.index', compact('estimulos'));
    }

    public function create()
    {
        return view('admin.estimulos.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'orden' => 'nullable|integer',
        ]);

        Estimulo::create([
            ...$validated,
            'fisio_id' => Auth::id(),
        ]);

        return redirect()->route('admin.estimulos.index')
            ->with('success', 'Estímulo creado correctamente.');
    }

    public function edit(Estimulo $estimulo)
    {
        return view('admin.estimulos.edit', compact('estimulo'));
    }

    public function update(Request $request, Estimulo $estimulo)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'orden' => 'required|integer',
        ]);

        $oldOrden = $estimulo->orden;
        $newOrden = $validated['orden'];

        if ($oldOrden != $newOrden) {
            // El global scope limita el reordenamiento a los estímulos propios.
            if ($newOrden < $oldOrden) {
                // Moving UP (e.g. 5 -> 2): Increment items in [2, 4]
                Estimulo::where('orden', '>=', $newOrden)
                    ->where('orden', '<', $oldOrden)
                    ->increment('orden');
            } else {
                // Moving DOWN (e.g. 2 -> 5): Decrement items in [3, 5]
                Estimulo::where('orden', '>', $oldOrden)
                    ->where('orden', '<=', $newOrden)
                    ->decrement('orden');
            }
        }

        $estimulo->update($validated);

        return redirect()->route('admin.estimulos.index')
            ->with('success', 'Estímulo actualizado correctamente.');
    }

    /**
     * Desactiva un estímulo y sus ejercicios (borrado lógico).
     *
     * Nada se borra físicamente: los planes donde ya estaban pautados siguen
     * mostrando el ejercicio y el feedback del paciente se conserva.
     */
    public function destroy(Estimulo $estimulo)
    {
        $estimulo->ejercicios()->get()->each(fn($ejercicio) => $ejercicio->delete());
        $estimulo->delete();

        return redirect()->route('admin.estimulos.index')
            ->with('success', 'Estímulo desactivado. Puede restaurarse desde la Papelera.');
    }
}
