@extends('layouts.app')
@section('title', 'Invitar Fisioterapeuta')

@section('content')
    <div class="page-header">
        <div>
            <h1>Invitar Fisioterapeuta</h1>
            <p class="subtitle">Genera un enlace de un solo uso para dar de alta a un/a compañero/a</p>
        </div>
    </div>

    @if(session('invite_link'))
        <div class="card" style="margin-bottom: 1.5rem">
            <div class="card-body">
                <label for="invite_link">Enlace de invitación (válido 7 días, un solo uso)</label>
                <div style="display: flex; gap: 10px; margin-top: 6px;">
                    <input type="text" id="invite_link" class="form-control" value="{{ session('invite_link') }}"
                        readonly onclick="this.select()">
                    <button type="button" class="btn btn-secondary"
                        onclick="navigator.clipboard.writeText(document.getElementById('invite_link').value)">Copiar</button>
                </div>
            </div>
        </div>
    @endif

    <div class="card" style="margin-bottom: 1.5rem">
        <div class="card-header">
            <h3>Generar nueva invitación</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.invitaciones.store') }}" method="POST" style="display: flex; gap: 10px;">
                @csrf
                <input type="email" name="email" class="form-control" placeholder="Email (opcional)"
                    value="{{ old('email') }}" style="max-width: 320px;">
                <button type="submit" class="btn btn-primary">Generar enlace</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Invitaciones enviadas</h3>
        </div>
        <div class="card-body">
            @if($invitaciones->isEmpty())
                <div class="empty-state">
                    <div class="icon">✉️</div>
                    <h3>Sin invitaciones todavía</h3>
                </div>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Invitado por</th>
                                <th>Estado</th>
                                <th>Expira</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invitaciones as $inv)
                                <tr>
                                    <td>{{ $inv->email ?? '—' }}</td>
                                    <td>{{ $inv->invitedBy->nombre_completo }}</td>
                                    <td>
                                        @if($inv->used_at)
                                            <span class="badge badge-success">Usada por {{ $inv->usedBy?->nombre_completo }}</span>
                                        @elseif($inv->expires_at->isPast())
                                            <span class="badge badge-warning">Caducada</span>
                                        @else
                                            <span class="badge badge-primary">Pendiente</span>
                                        @endif
                                    </td>
                                    <td>{{ $inv->expires_at->format('d/m/Y') }}</td>
                                    <td>
                                        @if($inv->isValid())
                                            <form action="{{ route('admin.invitaciones.destroy', $inv) }}" method="POST"
                                                onsubmit="return confirm('¿Revocar esta invitación?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Revocar</button>
                                            </form>
                                        @else
                                            <span style="color: var(--text-muted)">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
