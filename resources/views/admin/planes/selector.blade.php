@extends('layouts.app')
@section('title', 'Nuevo Plan para ' . $paciente->nombre_completo)

@section('content')
    <div class="page-header">
        <div>
            <h1>Nuevo Plan de Ejercicios</h1>
            <p class="subtitle">Paciente: {{ $paciente->nombre_completo }}</p>
        </div>
        <a href="{{ route('admin.pacientes.index') }}" class="btn btn-secondary">← Volver</a>
    </div>

    <div class="card" style="max-width: 800px; margin: 2rem auto;">
        <div class="card-body" style="padding: 3rem; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🤔</div>
            <h2>El paciente ya tiene planes previos</h2>
            <p style="margin-bottom: 2.5rem; color: var(--text-secondary);">¿Cómo quieres proceder con el nuevo plan?</p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="card option-card" style="border: 2px solid var(--primary); cursor: pointer;"
                    onclick="window.location.href='{{ route('admin.planes.create', ['clone_id' => $ultimoPlan->id]) }}'">
                    <div class="card-body">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📋➡️📋</div>
                        <h3>Continuar / Duplicar</h3>
                        <p style="font-size: 0.9rem; color: var(--text-secondary);">Copia los ejercicios del último plan
                            para ajustarlos.</p>
                        <span class="btn btn-primary" style="margin-top: 1rem;">Usar último plan</span>
                    </div>
                </div>

                <div class="card option-card" style="border: 2px solid var(--border); cursor: pointer;"
                    onclick="window.location.href='{{ route('admin.planes.create', ['paciente_id' => $paciente->id, 'skip_selector' => 1]) }}'">
                    <div class="card-body">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📂✨</div>
                        <h3>Plan Nuevo (Blanco)</h3>
                        <p style="font-size: 0.9rem; color: var(--text-secondary);">Empieza desde cero desactivando el plan
                            actual.</p>
                        <span class="btn btn-secondary" style="margin-top: 1rem;">Crear desde cero</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .option-card {
            transition: all 0.2s ease;
        }

        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-color: var(--primary) !important;
        }
    </style>
@endsection