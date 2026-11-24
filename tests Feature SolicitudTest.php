<?php

namespace Tests\Feature;

use App\Models\Apoyo;
use App\Models\BloqueoCita;
use App\Models\Cita;
use App\Models\CodigoPostal;
use App\Models\Empleado;
use App\Models\EventoComedor;
use App\Models\Queja;
use App\Models\RegistroComedor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\LegacyDatabaseTestCase;

class SolicitudTest extends LegacyDatabaseTestCase
{
    private function applicant(): array
    {
        CodigoPostal::firstOrCreate(['codigo_postal' => '55740', 'colonia' => 'Centro', 'municipio' => 'Tecámac']);

        return ['nombre' => 'Prueba', 'apellido_paterno' => 'Ciudadano', 'apellido_materno' => 'Local', 'celular_1' => '5512345678', 'celular_2' => '5587654321', 'correo' => 'prueba@example.test', 'calle' => 'Principal', 'no_exterior' => '10', 'no_interior' => '', 'codigo_postal' => '55740', 'colonia' => 'Centro', 'municipio' => 'Tecámac'];
    }

    private function event(int $capacity = 2): EventoComedor
    {
        return EventoComedor::create(['fecha' => today()->addDay()->toDateString(), 'hora_inicio' => '12:00:00', 'hora_fin' => '15:00:00', 'lugar' => 'Centro', 'cupo_maximo' => $capacity, 'activo' => true]);
    }

    private function meal(int $event): array
    {
        return [...$this->applicant(), 'evento_id' => $event, 'numero_personas' => 2, 'categoria_vulnerable' => 'Adultos mayores'];
    }

    public function test_public_forms_and_admin_pages_render(): void
    {
        Apoyo::create(['nombre' => 'Apoyo de prueba', 'activo' => true]);
        $this->event();
        foreach (['quejas', 'citas', 'apoyos', 'comedor'] as $module) {
            $button = match ($module) {
                'quejas' => 'Enviar reporte',
                'comedor' => 'Registrarme',
                default => 'Enviar solicitud',
            };
            $this->get('/solicitudes/'.$module)->assertOk()->assertSee($button)->assertSee('name="_token"', false);
            $this->get('/admin/solicitudes/'.$module)->assertRedirect('/login');
        }
        $this->actingAs(Empleado::factory()->create());
        foreach (['quejas', 'citas', 'apoyos', 'comedor'] as $module) {
            $this->get('/admin/solicitudes/'.$module)->assertOk();
        }
        $this->get('/admin/catalogos/apoyos')->assertOk();
        $this->get('/admin/catalogos/comedor/crear')->assertOk();
        $this->get('/admin/bloqueos')->assertOk();
    }

    public function test_comedor_cards_show_capacity_excluding_cancelled_registrations(): void
    {
        $event = $this->event(3);
        RegistroComedor::create([...$this->meal($event->id), 'estatus' => 'pendiente']);
        RegistroComedor::create([...$this->meal($event->id), 'estatus' => 'cancelado']);
        $this->get('/solicitudes/comedor?evento='.$event->id)
            ->assertOk()->assertSee('1 lugar(es)')->assertViewHas('selectedEvent', $event->id);
        $event->update(['cupo_maximo' => 2]);
        $this->get('/solicitudes/comedor?evento='.$event->id)
            ->assertOk()->assertSee('Cupo lleno')->assertViewHas('selectedEvent', null);
        $event->update(['activo' => false]);
        $this->get('/solicitudes/comedor')->assertOk()->assertSee('Sin fechas disponibles por el momento')->assertDontSee('id="comedorForm"', false);
    }

    public function test_original_form_controls_restore_input_and_keep_tracking_folio(): void
    {
        $this->withSession(['_old_input' => ['nombre' => 'Ana <Prueba>', 'tipo' => 'Agua', 'descripcion' => 'Falta agua', 'colonia' => 'Centro']])
            ->get('/solicitudes/quejas')->assertOk()
            ->assertSee('value="Ana &lt;Prueba&gt;"', false)
            ->assertSee('value="Agua" checked', false)
            ->assertSee('Falta agua')->assertSee('ISamuel12.png')
            ->assertDontSee('name="seccion_electoral"', false);
        $this->withSession(['_old_input' => ['fecha' => '2030-01-08', 'hora' => '09:20']])
            ->get('/solicitudes/citas')->assertOk()->assertSee('id="calendarGrid"', false)
            ->assertSee('id="fechaInput" required value="2030-01-08"', false)
            ->assertSee('data-slot="09:20"', false)->assertDontSee('type="date"', false);
        Apoyo::create(['nombre' => 'Apoyo de prueba', 'descripcion' => 'Descripción de prueba', 'activo' => true]);
        $this->withSession(['_old_input' => ['apoyo' => 'Apoyo de prueba']])
            ->get('/solicitudes/apoyos')->assertOk()->assertSee('value="Apoyo de prueba" checked', false)
            ->assertSee('Descripción de prueba')->assertSee('id="modalApoyos"', false);
        $this->withSession(['folio' => 'CIT-000001'])->get('/solicitudes/citas')
            ->assertOk()->assertSee('CIT-000001')->assertSee('id="stec-chat"', false);
    }

    public function test_appointment_rejects_same_day_non_tuesday_and_invalid_hour(): void
    {
        $this->travelTo(now()->setDate(2030, 1, 1)->startOfDay());
        $data = [...$this->applicant(), 'fecha' => '2030-01-01', 'hora' => '09:00'];
        $this->post('/solicitudes/citas', $data)->assertSessionHasErrors('fecha');
        $this->post('/solicitudes/citas', [...$data, 'fecha' => '2030-01-02'])->assertSessionHasErrors('fecha');
        $this->post('/solicitudes/citas', [...$data, 'fecha' => '2030-01-08', 'hora' => '03:00'])->assertSessionHasErrors('hora');
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_appointment_prevents_duplicate_slot_and_excludes_it_from_availability(): void
    {
        $date = today()->next('Tuesday')->toDateString();
        $data = [...$this->applicant(), 'fecha' => $date, 'hora' => '09:00'];
        $this->post('/solicitudes/citas', $data)->assertSessionHasNoErrors()->assertRedirect('/solicitudes/citas');
        $this->post('/solicitudes/citas', $data)->assertSessionHasErrors('hora');
        $this->assertDatabaseCount('citas', 1);
        $this->assertDatabaseHas('citas', ['hora' => '09:00:00', 'estatus' => 'solicitada']);
        $hours = $this->getJson('/citas/disponibilidad?fecha='.$date)->assertOk()->json('disponibles');
        $this->assertNotContains('09:00', $hours);
        $this->assertContains('09:20', $hours);
    }

    public function test_full_day_and_hour_blocks_prevent_booking(): void
    {
        $date = today()->next('Tuesday')->toDateString();
        BloqueoCita::create(['fecha' => $date, 'hora' => '09:00:00', 'dia_completo' => false]);
        $data = [...$this->applicant(), 'fecha' => $date, 'hora' => '09:00'];
        $this->post('/solicitudes/citas', $data)->assertSessionHasErrors('hora');
        BloqueoCita::create(['fecha' => $date, 'dia_completo' => true]);
        $this->post('/solicitudes/citas', [...$data, 'hora' => '09:20'])->assertSessionHasErrors('hora');
        $this->getJson('/citas/disponibilidad?fecha='.$date)->assertJsonPath('disponibles', []);
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_cancelled_appointment_releases_slot_but_cannot_be_reactivated_over_another_booking(): void
    {
        $date = today()->next('Tuesday')->toDateString();
        $data = [...$this->applicant(), 'fecha' => $date, 'hora' => '09:00:00'];
        $first = Cita::create([...$data, 'estatus' => 'cancelada']);
        $this->post('/solicitudes/citas', [...$data, 'hora' => '09:00'])->assertSessionHasNoErrors();
        $this->actingAs(Empleado::factory()->create())->patch('/admin/solicitudes/citas/'.$first->id, ['estatus' => 'aceptada'])->assertSessionHasErrors('hora');
        $this->assertSame('cancelada', $first->fresh()->estatus);
    }

    public function test_comedor_cupo_and_cancelled_registrations(): void
    {
        $event = $this->event();
        $data = $this->meal($event->id);
        $this->post('/solicitudes/comedor', $data)->assertSessionHasNoErrors();
        $this->post('/solicitudes/comedor', $data)->assertSessionHasErrors('numero_personas');
        $this->assertDatabaseCount('registros_comedor', 1);
        RegistroComedor::firstOrFail()->update(['estatus' => 'cancelado']);
        $this->post('/solicitudes/comedor', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('registros_comedor', 2);
    }

    public function test_comedor_inactive_past_and_invalid_category_are_rejected(): void
    {
        $event = $this->event();
        $data = $this->meal($event->id);
        $this->post('/solicitudes/comedor', [...$data, 'categoria_vulnerable' => 'inventada'])->assertSessionHasErrors('categoria_vulnerable');
        $event->update(['activo' => false]);
        $this->post('/solicitudes/comedor', $data)->assertSessionHasErrors('evento_id');
        $event->update(['activo' => true, 'fecha' => today()->subDay()->toDateString()]);
        $this->post('/solicitudes/comedor', $data)->assertSessionHasErrors('evento_id');
        $this->assertDatabaseCount('registros_comedor', 0);
    }

    public function test_support_must_exist_and_be_active_and_keeps_electoral_section(): void
    {
        $support = Apoyo::create(['nombre' => 'Apoyo de prueba', 'activo' => false]);
        $data = [...$this->applicant(), 'apoyo' => $support->nombre, 'seccion_electoral' => '0012'];
        $this->post('/solicitudes/apoyos', $data)->assertSessionHasErrors('apoyo');
        $support->update(['activo' => true]);
        $this->post('/solicitudes/apoyos', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('registros_apoyos', ['apoyo' => 'Apoyo de prueba', 'seccion_electoral' => '0012', 'estatus' => 'pendiente']);
    }

    public function test_mismatched_postal_address_is_rejected(): void
    {
        $data = [...$this->applicant(), 'fecha' => today()->next('Tuesday')->toDateString(), 'hora' => '09:00', 'municipio' => 'Otro'];
        $this->post('/solicitudes/citas', $data)->assertSessionHasErrors('colonia');
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_queja_evidence_is_required_validated_and_private(): void
    {
        Storage::fake('local');
        $data = [...$this->applicant(), 'tipo' => 'Alumbrado', 'descripcion' => 'Lámpara sin servicio', 'seccion_electoral' => '0012'];
        $this->post('/solicitudes/quejas', $data)->assertSessionHasErrors('evidencia');
        $this->post('/solicitudes/quejas', [...$data, 'evidencia' => UploadedFile::fake()->createWithContent('foto.png', '<?php echo "x";')])->assertSessionHasErrors('evidencia');
        $image = UploadedFile::fake()->createWithContent('foto.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aB1sAAAAASUVORK5CYII='));
        $this->post('/solicitudes/quejas', [...$data, 'evidencia' => $image])->assertSessionHasNoErrors();
        $record = Queja::firstOrFail();
        $this->assertSame('0012', $record->seccion_electoral);
        Storage::disk('local')->assertExists($record->evidencia_path);
        $this->get('/admin/solicitudes/quejas/'.$record->id.'/evidencia')->assertRedirect('/login');
        $this->actingAs(Empleado::factory()->create())->get('/admin/solicitudes/quejas/'.$record->id.'/evidencia')->assertDownload();
        $record->update(['evidencia_path' => '../../.env']);
        $this->get('/admin/solicitudes/quejas/'.$record->id.'/evidencia')->assertNotFound();
    }

    public function test_csrf_is_enforced_outside_test_environment(): void
    {
        $this->app['env'] = 'local';
        $this->post('/solicitudes/citas', [])->assertStatus(419);
        $this->post('/login', [])->assertStatus(419);
    }

    public function test_employee_blocks_and_unblocks_a_day(): void
    {
        $employee = Empleado::factory()->create();
        $date = today()->next('Tuesday')->toDateString();
        $this->actingAs($employee)->post('/admin/bloqueos', ['fecha' => $date, 'dia_completo' => 1, 'motivo' => 'Sin atención'])->assertSessionHasNoErrors();
        $block = BloqueoCita::firstOrFail();
        $this->assertSame($employee->id, $block->empleado_id);
        $this->getJson('/citas/disponibilidad?fecha='.$date)->assertJsonPath('disponibles', []);
        $this->delete('/admin/bloqueos/'.$block->id)->assertRedirect();
        $this->assertDatabaseCount('bloqueos_cita', 0);
    }

    public function test_employee_can_manage_events_and_support_catalog(): void
    {
        $this->actingAs(Empleado::factory()->create());
        $this->post('/admin/catalogos/apoyos', ['nombre' => 'Nuevo apoyo', 'descripcion' => 'Prueba', 'activo' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('apoyos', ['nombre' => 'Nuevo apoyo']);
        $data = ['fecha' => today()->addDay()->toDateString(), 'hora_inicio' => '12:00', 'hora_fin' => '15:00', 'lugar' => 'Centro', 'cupo_maximo' => 2, 'activo' => 1, 'menu' => 'Sopa'];
        $this->post('/admin/catalogos/comedor', $data)->assertSessionHasNoErrors();
        $event = EventoComedor::firstOrFail();
        $this->get('/admin/catalogos/comedor/'.$event->id.'/editar')->assertOk()->assertSee('Sopa');
        RegistroComedor::create([...$this->meal($event->id), 'estatus' => 'pendiente']);
        $this->put('/admin/catalogos/comedor/'.$event->id, [...$data, 'cupo_maximo' => 1])->assertSessionHasErrors('cupo_maximo');
        $this->assertSame(2, $event->fresh()->cupo_maximo);
    }

    public function test_unknown_modules_and_arbitrary_statuses_are_rejected(): void
    {
        $this->get('/solicitudes/empleados')->assertNotFound();
        $this->actingAs(Empleado::factory()->create())->get('/admin/solicitudes/empleados')->assertNotFound();
        $date = today()->next('Tuesday')->toDateString();
        $record = Cita::create([...$this->applicant(), 'fecha' => $date, 'hora' => '09:00:00', 'estatus' => 'solicitada']);
        $this->patch('/admin/solicitudes/citas/'.$record->id, ['estatus' => 'inventado'])->assertSessionHasErrors('estatus');
        $this->assertSame('solicitada', $record->fresh()->estatus);
        $this->get('/admin/solicitudes/citas/'.$record->id)->assertOk();
        $this->get('/admin/solicitudes/citas/exportar')->assertDownload('citas.csv');
    }
}
