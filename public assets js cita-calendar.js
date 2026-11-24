document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('citaForm');
    if (!form) return;
    const title = document.getElementById('calendarTitle');
    const grid = document.getElementById('calendarGrid');
    const dateInput = document.getElementById('fechaInput');
    const dateVisible = document.getElementById('fechaVisible');
    const info = document.getElementById('calendarSelectedInfo');
    const hourInput = document.getElementById('horaInput');
    const slots = [...document.querySelectorAll('#slotsGrid .slot-btn')];
    const months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const days = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    const today = new Date(`${form.dataset.today}T00:00:00`);
    let month = new Date(today.getFullYear(), today.getMonth(), 1);
    let selected = null;
    let serial = 0;
    let availableHours = [];
    const oldDate = dateInput.value;
    const oldHour = hourInput.value;
    dateInput.value = '';
    hourInput.value = '';

    function iso(date) {
        return `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
    }
    function readable(date) {
        return `${days[date.getDay()]} ${date.getDate()} de ${months[date.getMonth()]} de ${date.getFullYear()}`;
    }
    function clearSlots() {
        availableHours = [];
        hourInput.value = '';
        slots.forEach(button => {
            button.disabled = true;
            button.classList.remove('active');
            button.setAttribute('aria-pressed', 'false');
        });
    }
    function selectHour(button) {
        if (button.disabled || !availableHours.includes(button.dataset.slot)) return;
        hourInput.value = button.dataset.slot;
        slots.forEach(slot => {
            slot.classList.toggle('active', slot === button);
            slot.setAttribute('aria-pressed', String(slot === button));
        });
    }
    async function selectDate(date, restoreHour = '') {
        const current = ++serial;
        selected = date;
        dateInput.value = iso(date);
        dateVisible.value = readable(date);
        clearSlots();
        renderCalendar();
        info.textContent = `Fecha seleccionada: ${readable(date)}. Consultando horarios…`;
        try {
            const response = await fetch(`${form.dataset.availabilityUrl}?fecha=${iso(date)}`, {headers: {Accept: 'application/json'}});
            if (!response.ok) throw new Error('availability');
            const data = await response.json();
            if (current !== serial) return;
            if (!Array.isArray(data.disponibles)) throw new Error('availability');
            availableHours = data.disponibles;
            slots.forEach(button => {
                button.disabled = !availableHours.includes(button.dataset.slot);
                button.classList.toggle('disabled', button.disabled);
                button.textContent = button.dataset.slot + (button.disabled ? ' Ocupado' : '');
                button.title = button.disabled ? 'Horario no disponible' : '';
            });
            info.textContent = `Fecha seleccionada: ${readable(date)}` + (availableHours.length ? '' : '. No hay horarios disponibles. Selecciona otro martes.');
            const previous = slots.find(button => button.dataset.slot === restoreHour);
            if (previous) selectHour(previous);
        } catch {
            if (current === serial) info.textContent = 'No fue posible consultar los horarios. Selecciona nuevamente un martes para reintentar.';
        }
    }
    function renderCalendar() {
        title.textContent = `${months[month.getMonth()]} ${month.getFullYear()}`;
        grid.replaceChildren();
        const offset = (month.getDay() + 6) % 7;
        for (let i=0; i<offset; i++) {
            const empty = document.createElement('div');
            empty.className = 'calendar-day-ui empty';
            grid.append(empty);
        }
        const last = new Date(month.getFullYear(), month.getMonth()+1, 0).getDate();
        for (let day=1; day<=last; day++) {
            const date = new Date(month.getFullYear(), month.getMonth(), day);
            const enabled = date.getDay() === 2 && date > today;
            const chosen = selected && iso(date) === iso(selected);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `calendar-day-ui ${enabled ? 'enabled' : 'disabled'}${chosen ? ' selected' : ''}`;
            button.disabled = !enabled;
            button.setAttribute('aria-label', readable(date));
            button.setAttribute('aria-pressed', String(Boolean(chosen)));
            const number = document.createElement('span');
            number.className = 'calendar-day-number';
            number.textContent = day;
            const label = document.createElement('span');
            label.className = 'calendar-day-label';
            label.textContent = date.getDay() === 2 ? 'Martes' : '';
            button.append(number, label);
            button.addEventListener('click', () => selectDate(date));
            grid.append(button);
        }
    }
    document.getElementById('prevMonthBtn').addEventListener('click', () => {
        month = new Date(month.getFullYear(), month.getMonth()-1, 1);
        renderCalendar();
    });
    document.getElementById('nextMonthBtn').addEventListener('click', () => {
        month = new Date(month.getFullYear(), month.getMonth()+1, 1);
        renderCalendar();
    });
    slots.forEach(button => button.addEventListener('click', () => selectHour(button)));
    form.addEventListener('submit', event => {
        if (!dateInput.value || !availableHours.includes(hourInput.value)) {
            event.preventDefault();
            info.textContent = !dateInput.value ? 'Selecciona una fecha en el calendario.' : 'Selecciona un horario disponible.';
            grid.querySelector('button.enabled')?.focus();
        }
    });
    clearSlots();
    renderCalendar();
    if (/^\d{4}-\d{2}-\d{2}$/.test(oldDate)) {
        const previous = new Date(`${oldDate}T00:00:00`);
        if (previous > today && previous.getDay() === 2) {
            month = new Date(previous.getFullYear(), previous.getMonth(), 1);
            selectDate(previous, oldHour);
        }
    }
});
