<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Aislamiento por fisioterapeuta ("cada fisio es una isla").
 *
 * Se aplica automáticamente a los modelos que tienen columna `fisio_id`
 * (Plan, Estimulo, Ejercicio). Cualquier consulta hecha desde el panel de un
 * fisioterapeuta queda filtrada a sus propios registros, de modo que no es
 * posible "olvidarse" de filtrar en un endpoint nuevo.
 *
 * El scope NO se aplica cuando:
 *  - No hay usuario autenticado (seeders, jobs, consola, tests sin actingAs).
 *  - El usuario autenticado no es un fisioterapeuta (panel del paciente): en ese
 *    caso el aislamiento lo garantiza el propio controlador, que comprueba que
 *    el recurso pertenece al paciente autenticado.
 */
class FisioScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user || ! $user->isFisio()) {
            return;
        }

        $builder->where($model->getTable().'.fisio_id', $user->id);
    }
}
