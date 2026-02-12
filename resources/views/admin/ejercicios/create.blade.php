@extends('layouts.app')
@section('title', isset($ejercicio) ? 'Editar Ejercicio' : 'Nuevo Ejercicio')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ isset($ejercicio) ? 'Editar Ejercicio' : 'Nuevo Ejercicio' }}</h1>
        </div>
        <a href="{{ route('admin.ejercicios.index') }}" class="btn btn-secondary">← Volver</a>
    </div>

    <div class="card">
        <form method="POST"
            action="{{ isset($ejercicio) ? route('admin.ejercicios.update', $ejercicio) : route('admin.ejercicios.store') }}">
            @csrf
            @if(isset($ejercicio)) @method('PUT') @endif
            <div class="card-body">
                <div class="form-group">
                    <label for="nombre">Nombre del Ejercicio *</label>
                    <input type="text" name="nombre" id="nombre" class="form-control"
                        value="{{ old('nombre', $ejercicio->nombre ?? '') }}" placeholder="Ej: Press banca barra" required>
                </div>
                <div class="form-group">
                    <label for="estimulo_id">Estímulo *</label>
                    <select name="estimulo_id" id="estimulo_id" class="form-control" required>
                        <option value="">— Selecciona estímulo —</option>
                        @foreach($estimulos as $est)
                            <option value="{{ $est->id }}" {{ old('estimulo_id', $ejercicio->estimulo_id ?? '') == $est->id ? 'selected' : '' }}>
                                {{ $est->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="video_url">URL del Vídeo (YouTube)</label>
                    <input type="url" name="video_url" id="video_url" class="form-control"
                        value="{{ old('video_url', $ejercicio->video_url ?? '') }}"
                        placeholder="https://www.youtube.com/watch?v=...">
                    <small style="color: var(--text-muted)">Pega el enlace de YouTube del vídeo del ejercicio</small>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea name="descripcion" id="descripcion" class="form-control" rows="3"
                        placeholder="Notas sobre la técnica del ejercicio...">{{ old('descripcion', $ejercicio->descripcion ?? '') }}</textarea>
                </div>

                @if(isset($ejercicio) && $ejercicio->video_url)
                    <div class="form-group">
                        <label>Preview del vídeo</label>
                        <div style="max-width: 480px; border-radius: var(--radius-sm); overflow: hidden;">
                            <div style="position: relative; padding-top: 56.25%;">
                                <iframe src="{{ $ejercicio->embed_url }}"
                                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"
                                    allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <button type="submit"
                    class="btn btn-success">{{ isset($ejercicio) ? 'Guardar Cambios' : 'Crear Ejercicio' }}</button>
            </div>
        </form>
    </div>
@endsection