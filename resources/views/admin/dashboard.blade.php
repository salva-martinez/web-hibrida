@extends('layouts.app')
@section('title', 'Dashboard Admin')

@section('content')
    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p class="subtitle">Bienvenido, {{ auth()->user()->nombre }}</p>
        </div>
        <a href="{{ route('admin.planes.create') }}" class="btn btn-primary">+ Nuevo Plan</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">🏃</div>
            <div class="stat-info">
                <h3>{{ $stats['pacientes'] }}</h3>
                <p>Pacientes</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">📋</div>
            <div class="stat-info">
                <h3>{{ $stats['planes_activos'] }}</h3>
                <p>Planes Activos</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">🏋️</div>
            <div class="stat-info">
                <h3>{{ $stats['ejercicios'] }}</h3>
                <p>Ejercicios</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>📩 Últimos Feedback de Pacientes</h3>
        </div>
        <div class="card-body">
            @if($ultimosFeedbacks->isEmpty())
                <div class="empty-state">
                    <div class="icon">💬</div>
                    <h3>Aún no hay feedback</h3>
                    <p>Los pacientes podrán enviar su valoración cuando completen un plan.</p>
                </div>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Plan</th>
                                <th>Dureza</th>
                                <th>Comentario</th>
                                <th>Análisis IA</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ultimosFeedbacks as $fb)
                                @php $plan = $fb->planDia?->plan; @endphp
                                <tr>
                                    <td>{{ $plan?->paciente?->nombre_completo ?? '—' }}</td>
                                    <td>{{ $plan?->titulo ?? '—' }} (Día {{ $fb->planDia?->numero_dia ?? '—' }})</td>
                                    <td><span
                                            class="badge badge-{{ $fb->dureza > 7 ? 'danger' : ($fb->dureza > 4 ? 'warning' : 'success') }}">{{ $fb->dureza }}/10</span>
                                    </td>
                                    <td>{{ Str::limit($fb->comentario, 40) ?: '—' }}</td>
                                    <td>
                                        @if($fb->analisis_ia)
                                            <span class="badge badge-info" title="{{ $fb->analisis_ia }}">Ver Análisis 🤖</span>
                                        @else
                                            <span class="text-muted small">Pendiente</span>
                                        @endif
                                    </td>
                                    <td>{{ $fb->created_at->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection