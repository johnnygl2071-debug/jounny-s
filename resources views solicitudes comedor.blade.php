<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comedor Solidario | Samuel Te Escucha</title>
  <style>
    :root { --guinda: rgb(122,23,55); --bg: #f3f4f6; --border: #e5e7eb; --shadow: 0 14px 38px rgba(17,24,39,.08); }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: var(--bg); color: #1f2937; display:flex; flex-direction:column; min-height:100vh; }
    a { text-decoration: none; color: inherit; }

    /* TOPBAR */
    .topbar { background:#fff; border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; padding:12px 0; }
    .topbar-inner { max-width:1200px; margin:0 auto; padding:0 20px; display:flex; align-items:center; justify-content:space-between; }
    .brand { display:flex; align-items:center; gap:14px; }
    .brand-logo { width:55px; height:55px; border-radius:12px; overflow:hidden; border:1px solid var(--border); }
    .brand-logo img { width:100%; height:100%; object-fit:contain; }
    .brand h2 { margin:0; color:var(--guinda); font-size:22px; font-weight:800; }
    .nav a { padding:10px 15px; border-radius:10px; font-weight:700; color:#4b5563; transition:.2s; }
    .nav a:hover { background:var(--bg); color:var(--guinda); }

    /* SHELL */
    .wrap { max-width:1200px; margin:0 auto; padding:0 20px; }
    .page-shell { padding:40px 0 80px; flex:1; }
    .page-header { margin-bottom:30px; }
    .page-header h1 { margin:0 0 8px; color:var(--guinda); font-size:30px; font-weight:800; }
    .page-header p { margin:0; color:#6b7280; font-size:16px; }

    /* EVENTOS */
    .eventos-titulo { font-size:18px; font-weight:800; color:var(--guinda); margin-bottom:16px; }
    .eventos-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(260px,1fr)); gap:14px; margin-bottom:32px; }
    .evento-card {
      background:#fff; border:2px solid var(--border); border-radius:14px; padding:18px 20px;
      cursor:pointer; transition:border-color .2s, box-shadow .2s; position:relative;
    }
    .evento-card:hover { border-color:var(--guinda); box-shadow:0 4px 16px rgba(122,23,55,.12); }
    .evento-card.selected { border-color:var(--guinda); background:#fdf2f5; }
    .evento-card.sin-cupo { opacity:.55; cursor:not-allowed; }
    .evento-card__check {
      position:absolute; top:12px; right:12px; width:22px; height:22px; border-radius:50%;
      background:var(--guinda); color:#fff; font-size:13px; font-weight:700;
      display:none; align-items:center; justify-content:center;
    }
    .evento-card.selected .evento-card__check { display:flex; }
    .evento-card__fecha { font-size:17px; font-weight:800; color:var(--guinda); margin-bottom:5px; }
    .evento-card__hora { font-size:14px; color:#374151; font-weight:600; margin-bottom:3px; }
    .evento-card__lugar { font-size:13px; color:#6b7280; margin-bottom:5px; }
    .evento-card__desc { font-size:13px; color:#6b7280; margin-bottom:8px; }
    .chip { display:inline-block; font-size:12px; font-weight:700; padding:3px 10px; border-radius:20px; }
    .chip--disponible { background:#dcfce7; color:#166534; }
    .chip--lleno      { background:#fee2e2; color:#991b1b; }
    .chip--abierto    { background:#dbeafe; color:#1e40af; }

    /* FORM */
    .form-card { background:#fff; border:1px solid var(--border); border-radius:24px; padding:35px; box-shadow:var(--shadow); }
    .form-section-title { font-size:18px; font-weight:800; color:var(--guinda); margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border); }
    .form-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
    .form-group { display:flex; flex-direction:column; gap:8px; }
    .form-group.full { grid-column:1/-1; }
    .form-group.half { grid-column:span 2; }
    label { font-weight:700; color:#374151; font-size:14px; }
    input, textarea, select {
      width:100%; min-height:48px; border:1px solid #d1d5db; border-radius:12px;
      padding:12px 16px; font:inherit; background:#fff;
    }
    input:focus, textarea:focus, select:focus { outline:none; border-color:var(--guinda); box-shadow:0 0 0 4px rgba(122,23,55,.08); }
    textarea { min-height:90px; resize:vertical; }
    .form-actions { margin-top:30px; display:flex; justify-content:flex-end; gap:14px; }
    .btn { display:inline-flex; align-items:center; justify-content:center; min-height:48px; padding:0 28px; border-radius:12px; background:var(--guinda); color:#fff; font-weight:700; border:none; cursor:pointer; font:inherit; transition:.2s; }
    .btn:hover { opacity:.88; transform:translateY(-1px); }
    .btn-light { background:#f3f4f6; color:#374151; }

    /* MODAL */
    .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:1000; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
    .modal { background:#fff; border-radius:24px; padding:35px; width:min(480px,90%); text-align:center; box-shadow:0 25px 50px -12px rgba(0,0,0,.25); }
    .modal h3 { font-size:22px; margin-bottom:10px; }
    .modal p { color:#6b7280; margin-bottom:20px; white-space:pre-line; }

    /* SIN EVENTOS */
    .sin-eventos { background:#fff; border:1px solid var(--border); border-radius:14px; padding:40px 24px; text-align:center; color:#6b7280; }
    .sin-eventos h3 { color:#374151; margin-bottom:8px; font-size:20px; }

    /* FOOTER */
    .footer { background:var(--guinda); padding:0 20px; }
    .footer-inner { max-width:1200px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; min-height:62px; flex-wrap:wrap; gap:10px; color:#fff; font-size:14px; }
    .footer-logo { height:32px; object-fit:contain; filter:brightness(0) invert(1); }
    .footer a { color:rgba(255,255,255,.85); }
    .footer a:hover { color:#fff; }

    @media (max-width:900px) {
      .form-grid { grid-template-columns:1fr 1fr; }
      .form-group.full { grid-column:1/-1; }
      .form-group.half { grid-column:1/-1; }
    }
    @media (max-width:600px) {
      .form-grid { grid-template-columns:1fr; }
      .form-card { padding:20px; }
      .eventos-grid { grid-template-columns:1fr; }
    }
  </style>
<link rel="stylesheet" href="{{ asset('assets/css/solicitudes.css') }}">
</head>
<body>
<header class="topbar">
  <div class="topbar-inner">
    <div class="brand">
      <div class="brand-logo"><img src="{{ asset('assets/img/legislatura3.png') }}" alt="Samuel Te Escucha"></div>
      <div><h2>Samuel Te Escucha</h2></div>
    </div>
    <nav class="nav">
      <a href="{{ route('home') }}">Inicio</a>
      <a href="{{ route('solicitudes.create', 'citas') }}">Citas</a>
      <a href="{{ route('solicitudes.create', 'quejas') }}">Gestión</a>
      <a href="{{ route('solicitudes.create', 'apoyos') }}">Apoyos</a>
    </nav>
  </div>
</header>

<main class="wrap page-shell">
  @include('solicitudes.partials.feedback')
  <div class="page-header">
    <h1>Comedor Solidario</h1>
    <p>Consulta las próximas fechas del comedor y regístrate para asistir.</p>
  </div>

  @if ($options->isEmpty())
    <div class="sin-eventos">
      <h3>Sin fechas disponibles por el momento</h3>
      <p>Próximamente se publicarán nuevas fechas del Comedor Solidario. ¡Visítanos pronto!</p>
      <a href="{{ route('home') }}" class="btn btn-light" style="margin-top:20px;display:inline-flex;">← Volver al inicio</a>
    </div>
  @else

    <form method="POST" action="{{ route('solicitudes.store', 'comedor') }}" id="comedorForm">
      @csrf

      <!-- PASO 1: ELEGIR EVENTO -->
      <div class="form-card" style="margin-bottom:24px;">
        <p class="form-section-title">1. Selecciona la fecha del evento</p>

        <div class="eventos-grid" id="eventosGrid" role="radiogroup" aria-label="Fecha del evento">
          @foreach ($options as $ev)
            @php
                $cupo = (int) $ev->cupo_maximo;
                $disponibles = $cupo > 0 ? max(0, $cupo - (int) $ev->personas_ocupadas) : null;
                $sinCupo = $disponibles === 0;
                $preSelected = $selectedEvent == $ev->id;
                $fecha = \Illuminate\Support\Carbon::parse($ev->fecha)->locale('es');
            @endphp
            <div class="evento-card {{ $sinCupo ? 'sin-cupo' : '' }} {{ $preSelected ? 'selected' : '' }}"
                 data-id="{{ $ev->id }}" data-sin-cupo="{{ $sinCupo ? '1' : '0' }}"
                 role="radio" tabindex="{{ $sinCupo ? '-1' : '0' }}" aria-disabled="{{ $sinCupo ? 'true' : 'false' }}" aria-checked="{{ $preSelected ? 'true' : 'false' }}">
              <div class="evento-card__check">✓</div>
              <div class="evento-card__fecha">{{ ucfirst($fecha->translatedFormat('l d/m/Y')) }}</div>
              <div class="evento-card__hora">🕐 {{ substr($ev->hora_inicio, 0, 5) }} – {{ substr($ev->hora_fin, 0, 5) }}</div>
              @if ($ev->lugar)<div class="evento-card__lugar">📍 {{ $ev->lugar }}</div>@endif
              @if ($ev->descripcion)<div class="evento-card__desc">{{ $ev->descripcion }}</div>@endif
              @if ($ev->menu)<div class="evento-card__desc">🍽️ Menú: {{ $ev->menu }}</div>@endif
              @if ($sinCupo)
                <span class="chip chip--lleno">Cupo lleno</span>
              @elseif ($disponibles !== null)
                <span class="chip chip--disponible">{{ $disponibles }} lugar(es)</span>
              @else
                <span class="chip chip--abierto">Abierto</span>
              @endif
            </div>
          @endforeach
        </div>

        <input type="hidden" name="evento_id" id="eventoIdInput" value="{{ $selectedEvent }}">
      </div>

      <!-- PASO 2: DATOS PERSONALES -->
      <div class="form-card" id="formularioRegistro" style="{{ $selectedEvent ? '' : 'display:none;' }}">
        <p class="form-section-title">2. Datos de registro</p>

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
            <input id="celular_1" type="text" name="celular_1" maxlength="10" pattern="[0-9]{10}" inputmode="numeric"
                   placeholder="10 dígitos" required value="{{ old('celular_1') }}">
          </div>
          <div class="form-group">
            <label for="celular_2">Celular 2 (opcional)</label>
            <input id="celular_2" type="text" name="celular_2" maxlength="10" pattern="[0-9]{10}" inputmode="numeric"
                   placeholder="10 dígitos" value="{{ old('celular_2') }}">
          </div>
          <div class="form-group">
            <label for="correo">Correo electrónico (opcional)</label>
            <input id="correo" type="email" name="correo" value="{{ old('correo') }}">
          </div>

          <div class="form-group half">
            <label for="calle">Calle</label>
            <input id="calle" type="text" name="calle" required value="{{ old('calle') }}">
          </div>

          <div class="form-group">
            <label for="no_exterior">No. exterior</label>
            <input id="no_exterior" type="text" name="no_exterior" required value="{{ old('no_exterior') }}">
          </div>
          <div class="form-group">
            <label for="no_interior">No. interior (opcional)</label>
            <input id="no_interior" type="text" name="no_interior" value="{{ old('no_interior') }}">
          </div>

          <div class="form-group">
            <label for="codigo_postal">Código postal</label>
            <input type="text" id="codigo_postal" data-lookup-url="{{ route('cp.lookup') }}" name="codigo_postal"
                   maxlength="5" pattern="[0-9]{5}" inputmode="numeric"
                   placeholder="Ej: 55740" required
                   oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,5);" value="{{ old('codigo_postal') }}">
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
            <label for="numero_personas">Número de personas que asistirán</label>
            <input id="numero_personas" type="number" name="numero_personas" min="1" max="20" required value="{{ old('numero_personas', 1) }}">
          </div>

          <div class="form-group half">
            <label for="categoria_vulnerable">Grupo vulnerable al que perteneces</label>
            <select id="categoria_vulnerable" name="categoria_vulnerable" required>
              <option value="">Seleccione una opción</option>
              @foreach (config('samuel.categories') as $cat)
                <option value="{{ $cat }}" @selected(old('categoria_vulnerable') === $cat)>{{ $cat }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-group half">
            <label for="observaciones">Observaciones (alergias, necesidades especiales, etc.)</label>
            <textarea id="observaciones" name="observaciones" maxlength="500" placeholder="Opcional...">{{ old('observaciones') }}</textarea>
          </div>

        </div>

        <div class="form-actions">
          <a href="{{ route('home') }}" class="btn btn-light">Volver</a>
          <button type="submit" class="btn">Registrarme</button>
        </div>
      </div>

    </form>

  @endif
</main>

<footer class="footer">
  <div class="footer-inner">
    <div style="display:flex;align-items:center;gap:10px;">
      <img src="{{ asset('assets/img/SM LOGO-07.png') }}" alt="Logo" class="footer-logo">
      <span>© 2026 Samuel Te Escucha</span>
    </div>
    <div>Oficina virtual</div>
    <a href="{{ route('login') }}" onclick="alert('Solo el personal tiene acceso a este apartado')">Personal</a>
  </div>
</footer>
<script src="{{ asset('assets/js/solicitudes.js') }}" defer></script>
<x-chatbot />
</body>
</html>
