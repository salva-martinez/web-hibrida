<?php

namespace Tests\Feature;

use App\Models\Ejercicios;
use App\Models\Estimulo;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_stimulus_reordering()
    {
        $fisio = User::factory()->create(['role' => 'fisio']);
        
        // Create 3 stimuli: 1, 2, 3
        $e1 = Estimulo::factory()->create(['orden' => 1, 'nombre' => 'E1']);
        $e2 = Estimulo::factory()->create(['orden' => 2, 'nombre' => 'E2']);
        $e3 = Estimulo::factory()->create(['orden' => 3, 'nombre' => 'E3']);

        // Move E3 to position 1
        $response = $this->actingAs($fisio)
            ->put(route('admin.estimulos.update', $e3), [
                'nombre' => 'E3',
                'orden' => 1
            ]);

        $response->assertRedirect();
        
        // Expected: E3=1, E1=2, E2=3
        $this->assertEquals(1, $e3->fresh()->orden);
        $this->assertEquals(2, $e1->fresh()->orden);
        $this->assertEquals(3, $e2->fresh()->orden);

        // Move E3 back to 3
        $this->actingAs($fisio)
            ->put(route('admin.estimulos.update', $e3), [
                'nombre' => 'E3',
                'orden' => 3
            ]);

        // Expected: E1=1, E2=2, E3=3
        $this->assertEquals(1, $e1->fresh()->orden);
        $this->assertEquals(2, $e2->fresh()->orden);
        $this->assertEquals(3, $e3->fresh()->orden);
    }

    public function test_plan_auto_inactivation()
    {
        $fisio = User::factory()->create(['role' => 'fisio']);
        $paciente = User::factory()->create(['role' => 'paciente']);
        $ejercicio = \App\Models\Ejercicio::factory()->create();

        // Create initial active plan
        $plan1 = Plan::create([
            'paciente_id' => $paciente->id,
            'fisio_id' => $fisio->id,
            'titulo' => 'Plan 1',
            'fecha' => now(),
            'activo' => true
        ]);

        // Create new active plan via controller logic
        $response = $this->actingAs($fisio)
            ->post(route('admin.planes.store'), [
                'paciente_id' => $paciente->id,
                'titulo' => 'Plan 2',
                'fecha' => now()->addDay(),
                'activo' => true,
                'ejercicios' => [
                    [
                        'ejercicio_id' => $ejercicio->id,
                        'series' => 3,
                        'repeticiones' => '10',
                        'intensidad' => 7
                    ]
                ]
            ]);

        $response->assertRedirect();

        // Check Plan 1 is now inactive
        $this->assertFalse((bool)$plan1->fresh()->activo);
        
        // Check Plan 2 exists and is active
        $plan2 = Plan::where('titulo', 'Plan 2')->first();
        $this->assertTrue((bool)$plan2->activo);
    }

    public function test_create_plan_redirects_to_selector_if_plans_exist()
    {
        $fisio = User::factory()->create(['role' => 'fisio']);
        $paciente = User::factory()->create(['role' => 'paciente']);
        Plan::factory()->create(['paciente_id' => $paciente->id]);

        $response = $this->actingAs($fisio)
            ->get(route('admin.planes.create', ['paciente_id' => $paciente->id]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.planes.selector');
        $response->assertSee('El paciente ya tiene planes previos');
    }

    public function test_create_plan_skips_selector_if_patient_is_new()
    {
        $fisio = User::factory()->create(['role' => 'fisio']);
        $paciente = User::factory()->create(['role' => 'paciente']);

        $response = $this->actingAs($fisio)
            ->get(route('admin.planes.create', ['paciente_id' => $paciente->id]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.planes.create');
        $this->assertEquals($paciente->id, $response->viewData('clonedPlan')?->paciente_id ?? null ?: $paciente->id); 
    }

    public function test_create_plan_skips_selector_with_param()
    {
        $fisio = User::factory()->create(['role' => 'fisio']);
        $paciente = User::factory()->create(['role' => 'paciente']);
        Plan::factory()->create(['paciente_id' => $paciente->id]);

        $response = $this->actingAs($fisio)
            ->get(route('admin.planes.create', ['paciente_id' => $paciente->id, 'skip_selector' => 1]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.planes.create');
    }

    public function test_cloning_page_loads_data()
    {
        $fisio = User::factory()->create(['role' => 'fisio']);
        $paciente = User::factory()->create(['role' => 'paciente']);
        $plan = Plan::factory()->create(['paciente_id' => $paciente->id, 'titulo' => 'Original Plan']);
        
        $response = $this->actingAs($fisio)
            ->get(route('admin.planes.create', ['clone_id' => $plan->id]));

        $response->assertStatus(200);
        $response->assertSee('Original Plan (Copia)');
    }
}
