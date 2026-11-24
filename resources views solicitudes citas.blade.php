<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitar Cita | Samuel Te Escucha</title>
  <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/cita.css') }}">
  <style>
    @media (max-width: 768px) {
      #calendarGrid {
        display: grid !important;
        grid-template-columns: repeat(7, 1fr) !important;
        gap: 6px !important;
      }
      #calendarGrid .calendar-day-ui,
      #calendarGrid button,
      #calendarGrid div {
        width: auto !important; min-width: 0 !important; max-width: none !important;
        height: 54px !important; min-height: 54px !important; padding: 6px !important;
      }
    }
  </style>
<link rel="stylesheet" href="{{ asset('assets/css/solicitudes.css') }}">
</head>
<body>
<header class="topbar">
  <div class="wrap topbar__inner">
    <div class="brand">
      <div class="brand__logo">
        <img src="{{ asset('assets/img/legislatura3.png') }}" alt="Samuel Te Escucha">
      </div>
      <div>
        <h2>Samuel Te Escucha</h2>
      </div>
    </div>
    <nav class="nav">
      <a href="{{ route('home') }}">Inicio</a>
      <a href="{{ route('solicitudes.create', 'quejas') }}">Gestión</a>
      <a href="{{ route('solicitudes.create', 'apoyos') }}">Apoyos</a>
      <a href="{{ route('solicitudes.create', 'comedor') }}">Comedor</a>
    </nav>
  </div>
</header>

<main class="wrap page-shell">
  @include('solicitudes.partials.feedback')
  <div class="page-header">
    <h1>Martes ciudadano</h1>
    <p>Selecciona un martes disponible y envía tu solicitud para revisión.</p>
  </div>

  <section class="form-card">
    <form method="POST" action="{{ route('solicitudes.store', 'citas') }}" id="citaForm" data-availability-url="{{ route('citas.availability') }}" data-today="{{ today()->toDateString() }}" enctype="multipart/form-data">
      @csrf
      <div class="appointment-layout">

        <!-- Calendario -->
        <div class="appointment-calendar-card">
          <div class="calendar-toolbar">
            <button type="button" class="calendar-nav-btn" id="prevMonthBtn" aria-label="Mes anterior">‹</button>
            <h3 id="calendarTitle">Mes Año</h3>
            <button type="button" class="calendar-nav-btn" id="nextMonthBtn" aria-label="Mes siguiente">›</button>
          </div>
          <div class="calendar-weekdays">
            <div>Lun</div><div>Mar</div><div>Mié</div><div>Jue</div><div>Vie</div><div>Sáb</div><div>Dom</div>
          </div>
          <div class="calendar-grid-ui" id="calendarGrid"></div>
          <div class="calendar-legend">
            <span><span class="legend-dot legend-dot--enabled"></span> Martes disponible</span>
            <span><span class="legend-dot legend-dot--disabled"></span> No disponible</span>
            <span><span class="legend-dot legend-dot--selected"></span> Seleccionado</span>
          </div>
          <div class="calendar-selected-info" id="calendarSelectedInfo" role="status">
            Aún no has seleccionado una fecha.
          </div>
        </div>

        <!-- Formulario -->
        <div class="appointment-form-card">
          <div class="form-grid">

            <div class="form-group">
              <label for="nombre">Nombre</label>
              <input id="nombre" type="text" name="nombre" required value="{{ old('nombre') }}">
            </div>

            <div class="form-group">
              <label for="apellido_paterno">Apellido paterno</label>
              <input id="apellido_paterno" type="text" name="apellido_paterno" required value="{{ old('apellido_paterno') }}">
            </div>

            <div class="form-group">
              <label for="apellido_materno">Apellido materno</label>
              <input id="apellido_materno" type="text" name="apellido_materno" required value="{{ old('apellido_materno') }}">
            </div>

            <div class="form-group">
              <label for="celular_1">Celular 1</label>
              <input id="celular_1" type="text" name="celular_1" maxlength="10" pattern="[0-9]{10}" required value="{{ old('celular_1') }}">
            </div>

            <div class="form-group">
              <label for="celular_2">Celular 2</label>
              <input id="celular_2" type="text" name="celular_2" maxlength="10" pattern="[0-9]{10}" required value="{{ old('celular_2') }}">
            </div>

            <div class="form-group">
              <label for="correo">Correo electrónico</label>
              <input id="correo" type="email" name="correo" required value="{{ old('correo') }}">
            </div>

            <div class="form-group">
              <label for="calle">Calle</label>
              <input id="calle" type="text" name="calle" required value="{{ old('calle') }}">
            </div>

            <div class="form-group">
              <label for="no_exterior">No. exterior</label>
              <input id="no_exterior" type="text" name="no_exterior" required value="{{ old('no_exterior') }}">
            </div>

            <div class="form-group">
              <label for="no_interior">No. interior</label>
              <input id="no_interior" type="text" name="no_interior" value="{{ old('no_interior') }}">
            </div>

            <div class="form-group">
              <label for="codigo_postal">Código postal</label>
              <input
                type="text"
                id="codigo_postal" data-lookup-url="{{ route('cp.lookup') }}"
                name="codigo_postal"
                maxlength="5"
                pattern="[0-9]{5}"
                inputmode="numeric"
                placeholder="Ej: 55740"
                required
                oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,5);"
               value="{{ old('codigo_postal') }}">
            </div>

            <div class="form-group">
              <label for="colonia">Colonia</label>
              <select name="colonia" id="colonia" data-old="{{ old('colonia') }}" required>
                <option value="">Seleccione una colonia</option>
              </select>
            </div>

            <div class="form-group">
              <label for="municipio">Municipio</label>
              <input type="text" name="municipio" id="municipio" readonly required value="{{ old('municipio') }}">
            </div>

            <div class="form-group">
              <label for="fechaVisible">Fecha seleccionada</label>
              <input type="text" id="fechaVisible" placeholder="Selecciona un martes en el calendario" readonly>
              <input type="hidden" name="fecha" id="fechaInput" required value="{{ old('fecha') }}">
            </div>

            <div class="form-group form-group--full">
              <label>Horario disponible</label>
              <div class="slots-grid" id="slotsGrid">
                <button type="button" class="slot-btn" data-slot="09:00">09:00 - 09:20</button>
                <button type="button" class="slot-btn" data-slot="09:20">09:20 - 09:40</button>
                <button type="button" class="slot-btn" data-slot="09:40">09:40 - 10:00</button>
                <button type="button" class="slot-btn" data-slot="10:00">10:00 - 10:20</button>
                <button type="button" class="slot-btn" data-slot="10:20">10:20 - 10:40</button>
                <button type="button" class="slot-btn" data-slot="10:40">10:40 - 11:00</button>
                <button type="button" class="slot-btn" data-slot="11:00">11:00 - 11:20</button>
                <button type="button" class="slot-btn" data-slot="11:20">11:20 - 11:40</button>
                <button type="button" class="slot-btn" data-slot="11:40">11:40 - 12:00</button>
                <button type="button" class="slot-btn" data-slot="12:00">12:00 - 12:20</button>
                <button type="button" class="slot-btn" data-slot="12:20">12:20 - 12:40</button>
                <button type="button" class="slot-btn" data-slot="12:40">12:40 - 13:00</button>
                <button type="button" class="slot-btn" data-slot="13:00">13:00 - 13:20</button>
                <button type="button" class="slot-btn" data-slot="13:20">13:20 - 13:40</button>
                <button type="button" class="slot-btn" data-slot="13:40">13:40 - 14:00</button>
                <button type="button" class="slot-btn" data-slot="14:00">14:00 - 14:20</button>
                <button type="button" class="slot-btn" data-slot="14:20">14:20 - 14:40</button>
                <button type="button" class="slot-btn" data-slot="14:40">14:40 - 15:00</button>
              </div>
              <input type="hidden" name="hora" id="horaInput" required value="{{ old('hora') }}">
            </div>

            <div class="form-group form-group--full">
              <label for="motivo">Motivo de la cita</label>
              <textarea id="motivo" name="motivo" maxlength="500" placeholder="Explique brevemente el asunto de la cita...">{{ old('motivo') }}</textarea>
            </div>

          </div>

          <div class="form-actions">
            <a href="{{ route('home') }}" class="btn btn--light">Volver</a>
            <button type="submit" class="btn">Enviar solicitud</button>
          </div>
        </div>



      </div>
    </form>
  </section>
</main>

<footer class="footer">
  <div class="wrap footer__inner">
    <div class="footer-left">
      <img src="{{ asset('assets/img/SM LOGO-07.png') }}" alt="Logo" class="footer-logo">
      <span>© 2026 Samuel Te Escucha</span>
    </div>
    <div>Oficina virtual</div>
    <a href="{{ route('login') }}" onclick="alert('Solo el personal tiene acceso a este apartado')">Personal</a>
  </div>
</footer>
<script src="{{ asset('assets/js/solicitudes.js') }}" defer></script>
 <script src="{{ asset('assets/js/cita-calendar.js') }}" defer></script>
<x-chatbot />
</body>
</html>
