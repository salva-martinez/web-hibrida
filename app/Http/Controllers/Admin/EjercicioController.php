<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ejercicio;
use App\Models\Estimulo;
use Illuminate\Http\Request;

class EjercicioController extends Controller
{
    public function index()
    {
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
            'video_url' => 'nullable|url',
            'descripcion' => 'nullable|string',
        ]);

        Ejercicio::create($validated);

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
            'video_url' => 'nullable|url',
            'descripcion' => 'nullable|string',
        ]);

        $ejercicio->update($validated);

        return redirect()->route('admin.ejercicios.index')
            ->with('success', 'Ejercicio actualizado correctamente.');
    }

    public function destroy(Ejercicio $ejercicio)
    {
        $ejercicio->delete();
        return redirect()->route('admin.ejercicios.index')
            ->with('success', 'Ejercicio eliminado correctamente.');
    }
}
