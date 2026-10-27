/**
 * Raki Daulat — comportamiento del sitio (JavaScript sin dependencias).
 *  - Menú móvil
 *  - Galería con filtros y visor de imágenes
 *  - Carta tipo libro
 *  - Envío de formularios (contacto y reserva) con validación
 */
(function () {
  'use strict';

  var WHATSAPP = '18296846325';

  document.addEventListener('DOMContentLoaded', function () {
    initYear();
    initNav();
    initGallery();
    initLightbox();
    initBook();
    initReservationDate();
    document.querySelectorAll('form[data-endpoint]').forEach(initForm);
  });

  /* ------------------------------------------------------------ Utilidades */
  function initYear() {
    document.querySelectorAll('[data-year]').forEach(function (el) {
      el.textContent = new Date().getFullYear();
    });
  }

  function todayISO() {
    var d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 10);
  }

  /* ------------------------------------------------------------ Navegación */
  function initNav() {
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('site-nav');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('is-open')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
      }
    });
  }

  /* ------------------------------------------------------------ Galería */
  function initGallery() {
    var buttons = document.querySelectorAll('.filter-btn');
    var items = document.querySelectorAll('.gallery-item');
    if (!buttons.length) return;

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var filter = btn.dataset.filter;
        buttons.forEach(function (b) { b.setAttribute('aria-pressed', String(b === btn)); });
        items.forEach(function (item) {
          item.hidden = filter !== 'todos' && item.dataset.category !== filter;
        });
      });
    });
  }

  function initLightbox() {
    var dialog = document.getElementById('lightbox');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    var img = dialog.querySelector('img');
    var caption = dialog.querySelector('p');

    document.querySelectorAll('[data-lightbox]').forEach(function (trigger) {
      trigger.addEventListener('click', function () {
        var thumb = trigger.querySelector('img');
        img.src = trigger.dataset.lightbox;
        img.alt = thumb ? thumb.alt : '';
        caption.textContent = img.alt;
        dialog.showModal();
      });
    });

    dialog.querySelector('.lightbox__close').addEventListener('click', function () { dialog.close(); });
    // Cerrar al hacer clic fuera de la imagen
    dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
  }

  /* ------------------------------------------------------------ Carta */
  function initBook() {
    var book = document.getElementById('book');
    if (!book) return;
    var stacked = window.matchMedia('(max-width: 960px)');

    book.querySelector('[data-book-open]').addEventListener('click', function () {
      if (stacked.matches) {
        document.getElementById('menu-inicio').scrollIntoView({ behavior: 'smooth' });
      } else {
        book.classList.add('is-open');
      }
    });
    book.querySelector('[data-book-close]').addEventListener('click', function () {
      book.classList.remove('is-open');
    });
  }

  /* ------------------------------------------------------------ Reservas */
  function initReservationDate() {
    var date = document.querySelector('form[data-kind="reserva"] input[name="fecha"]');
    if (!date) return;
    date.min = todayISO();
    var max = new Date();
    max.setMonth(max.getMonth() + 3);
    max.setMinutes(max.getMinutes() - max.getTimezoneOffset());
    date.max = max.toISOString().slice(0, 10);
  }

  /* ------------------------------------------------------------ Formularios */
  function validateField(input) {
    var field = input.closest('.field');
    if (!field) return true;
    var valid = input.checkValidity();

    // Regla extra: no se reciben reservas los lunes (día de cierre)
    if (valid && input.name === 'fecha' && input.value) {
      var day = new Date(input.value + 'T12:00:00').getDay();
      if (day === 1) valid = false;
    }
    // Los campos con espacios en blanco no cuentan como completos
    if (valid && input.required && !input.value.trim()) valid = false;

    field.classList.toggle('is-invalid', !valid);
    input.setAttribute('aria-invalid', String(!valid));
    var msg = field.querySelector('.error-msg');
    if (msg) input.setAttribute('aria-describedby', msg.id);
    return valid;
  }

  function showAlert(form, type, html) {
    var alert = form.querySelector('.alert');
    alert.className = 'alert alert--' + type;
    alert.innerHTML = html;
    alert.hidden = false;
    alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function whatsappFallback(form) {
    var data = new FormData(form);
    var text;
    if (form.dataset.kind === 'reserva') {
      text = 'Hola, quiero reservar una mesa.\n' +
        'Nombre: ' + data.get('nombre') + '\n' +
        'Fecha: ' + data.get('fecha') + ' a las ' + data.get('hora') + '\n' +
        'Personas: ' + data.get('personas') + '\n' +
        'Teléfono: ' + data.get('telefono') +
        (data.get('comentarios') ? '\nComentarios: ' + data.get('comentarios') : '');
    } else {
      text = 'Hola, soy ' + data.get('nombre') + ' (' + data.get('correo') + ').\n' + data.get('mensaje');
    }
    return 'https://wa.me/' + WHATSAPP + '?text=' + encodeURIComponent(text);
  }

  function escapeHTML(str) {
    return String(str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function initForm(form) {
    var controls = form.querySelectorAll('.field input, .field select, .field textarea');
    var submit = form.querySelector('[type="submit"]');
    var submitLabel = submit.textContent;

    controls.forEach(function (input) {
      input.addEventListener('blur', function () { if (input.value) validateField(input); });
      input.addEventListener('input', function () {
        if (input.closest('.field').classList.contains('is-invalid')) validateField(input);
      });
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var firstInvalid = null;
      controls.forEach(function (input) {
        if (!validateField(input) && !firstInvalid) firstInvalid = input;
      });
      if (firstInvalid) {
        firstInvalid.focus();
        return;
      }

      submit.disabled = true;
      submit.textContent = 'Enviando…';

      fetch(form.dataset.endpoint, {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json' }
      })
        .then(function (res) {
          var type = res.headers.get('content-type') || '';
          if (type.indexOf('application/json') === -1) throw new Error('sin-backend');
          return res.json().then(function (json) { return { status: res.status, body: json }; });
        })
        .then(function (r) {
          if (r.body.ok) {
            form.reset();
            showAlert(form, 'success', escapeHTML(r.body.message));
            return;
          }
          // Errores de validación devueltos por el servidor
          if (r.body.errors) {
            Object.keys(r.body.errors).forEach(function (name) {
              var input = form.elements[name];
              if (!input) return;
              var field = input.closest('.field');
              field.classList.add('is-invalid');
              field.querySelector('.error-msg').textContent = r.body.errors[name];
            });
          }
          showAlert(form, 'error', escapeHTML(r.body.message || 'No pudimos procesar tu solicitud.'));
        })
        .catch(function () {
          // Sin servidor PHP (por ejemplo, en GitHub Pages): ofrecer WhatsApp
          showAlert(form, 'error',
            'No pudimos enviar el formulario en este momento. ' +
            '<a href="' + whatsappFallback(form) + '" target="_blank" rel="noopener">Envíalo por WhatsApp</a> ' +
            'o llámanos al +1 829-684-6325.');
        })
        .finally(function () {
          submit.disabled = false;
          submit.textContent = submitLabel;
        });
    });
  }
})();
