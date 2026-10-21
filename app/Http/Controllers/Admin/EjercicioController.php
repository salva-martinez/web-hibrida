<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ejercicio;
use App\Models\Estimulo;
use App\Rules\YouTubeUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EjercicioController extends Controller
{
    public function index()
    {
        // El aislamiento global limita la lista a los ejercicios de este fisio.
        $ejercicios = Ejercicio::with('estimulo')->latest()->get();

        return view('admin.ejercicios.index', compact('ejercicios'));
    }

    public function create()
    {
        $estimulos = Estimulo::orderBy('orden')->get();

        return view('admin.ejercicios.create', compact('estimulos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'estimulo_id' => 'required|exists:estimulos,id',
            'video_url' => ['nullable', new YouTubeUrl],
            'descripcion' => 'nullable|string',
        ]);

        Ejercicio::create([
            ...$validated,
            'fisio_id' => Auth::id(),
        ]);

        return redirect()->route('admin.ejercicios.index')
            ->with('success', 'Ejercicio creado correctamente.');
    }

    public function edit(Ejercicio $ejercicio)
    {
        $estimulos = Estimulo::orderBy('orden')->get();

        return view('admin.ejercicios.edit', compact('ejercicio', 'estimulos'));
    }

    public function update(Request $request, Ejercicio $ejercicio)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'estimulo_id' => 'required|exists:estimulos,id',
            'video_url' => ['nullable', new YouTubeUrl],
            'descripcion' => 'nullable|string',
        ]);

        $ejercicio->update($validated);

        return redirect()->route('admin.ejercicios.index')
            ->with('success', 'Ejercicio actualizado correctamente.');
    }

    /**
     * Desactiva un ejercicio (borrado lógico).
     *
     * El ejercicio desaparece del catálogo, pero sigue visible en los planes
     * antiguos donde ya fue pautado, con su feedback intacto.
     */
    public function destroy(Ejercicio $ejercicio)
    {
        $ejercicio->delete();

        return redirect()->route('admin.ejercicios.index')
            ->with('success', 'Ejercicio desactivado. Puede restaurarse desde la Papelera.');
    }
}
