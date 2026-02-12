@extends('layouts.app')
@section('title', isset($estimulo) ? 'Editar Estímulo' : 'Nuevo Estímulo')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ isset($estimulo) ? 'Editar Estímulo' : 'Nuevo Estímulo' }}</h1>
        </div>
        <a href="{{ route('admin.estimulos.index') }}" class="btn btn-secondary">← Volver</a>
    </div>

    <div class="card">
        <form method="POST"
            action="{{ isset($estimulo) ? route('admin.estimulos.update', $estimulo) : route('admin.estimulos.store') }}">
            @csrf
            @if(isset($estimulo)) @method('PUT') @endif
            <div class="card-body">
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" name="nombre" id="nombre" class="form-control"
                        value="{{ old('nombre', $estimulo->nombre ?? '') }}" placeholder="Ej: 1. Básico Estructural-Neural"
                        required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea name="descripcion" id="descripcion" class="form-control" rows="3"
                        placeholder="Descripción del grupo de estímulo...">{{ old('descripcion', $estimulo->descripcion ?? '') }}</textarea>
                </div>
                <div class="form-group">
                    <label for="orden">Orden</label>
                    <input type="number" name="orden" id="orden" class="form-control"
                        value="{{ old('orden', $estimulo->orden ?? 0) }}" min="0" style="max-width: 120px">
                </div>
            </div>
            <div class="card-footer">
                <button type="submit"
                    class="btn btn-success">{{ isset($estimulo) ? 'Guardar Cambios' : 'Crear Estímulo' }}</button>
            </div>
        </form>
    </div>
@endsection