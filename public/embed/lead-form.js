/*!
 * Raikan Cinta — lead capture widget
 * ----------------------------------
 * Drop-in, dependency-free popup form for raikancinta.com. It captures a
 * visitor's name / phone / email into the Sales CRM dashboard BEFORE handing
 * them off to WhatsApp, so leads are never keyed in by hand.
 *
 * Usage on raikancinta.com (no build step required):
 *
 *   <a href="#" class="js-raikan-lead">Chat on WhatsApp</a>
 *
 *   <script
 *     src="https://YOUR-DASHBOARD-DOMAIN/embed/lead-form.js"
 *     data-api="https://YOUR-DASHBOARD-DOMAIN/api/leads"
 *     data-trigger=".js-raikan-lead"
 *     defer></script>
 *
 * Any element matching data-trigger opens the form. After a successful save
 * the visitor is redirected to the WhatsApp URL the API returns. You can also
 * open it programmatically with window.RaikanLead.open().
 *
 * The dashboard must allow this site's origin in CORS_ALLOWED_ORIGINS.
 */
(function () {
  'use strict';

  var script = document.currentScript;
  var API_URL = (script && script.getAttribute('data-api')) || '/api/leads';
  var TRIGGER = (script && script.getAttribute('data-trigger')) || '.js-raikan-lead';
  var ACCENT = (script && script.getAttribute('data-accent')) || '#4f46e5';

  var STYLE_ID = 'raikan-lead-styles';
  var overlay = null;

  function injectStyles() {
    if (document.getElementById(STYLE_ID)) return;
    var css =
      '.rl-overlay{position:fixed;inset:0;background:rgba(15,23,42,.6);display:flex;' +
      'align-items:center;justify-content:center;z-index:2147483647;padding:16px;' +
      'font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}' +
      '.rl-modal{background:#fff;color:#111827;border-radius:14px;max-width:400px;width:100%;' +
      'padding:24px;box-shadow:0 20px 60px rgba(0,0,0,.3);box-sizing:border-box;animation:rl-in .18s ease-out}' +
      '@keyframes rl-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}' +
      '.rl-title{font-size:18px;font-weight:700;margin:0 0 4px}' +
      '.rl-sub{font-size:13px;color:#6b7280;margin:0 0 18px}' +
      '.rl-field{margin-bottom:14px}' +
      '.rl-label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px}' +
      '.rl-input{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d1d5db;' +
      'border-radius:9px;font-size:14px;outline:none;transition:border-color .15s,box-shadow .15s}' +
      '.rl-input:focus{border-color:' + ACCENT + ';box-shadow:0 0 0 3px rgba(79,70,229,.15)}' +
      '.rl-error{color:#dc2626;font-size:12px;margin-top:4px;display:none}' +
      '.rl-input.rl-invalid{border-color:#dc2626}' +
      '.rl-btn{width:100%;background:' + ACCENT + ';color:#fff;border:0;border-radius:9px;' +
      'padding:12px;font-size:15px;font-weight:600;cursor:pointer;transition:opacity .15s}' +
      '.rl-btn:hover{opacity:.92}.rl-btn:disabled{opacity:.6;cursor:not-allowed}' +
      '.rl-close{position:absolute;top:14px;right:16px;background:0;border:0;font-size:22px;' +
      'line-height:1;color:#9ca3af;cursor:pointer}' +
      '.rl-modal{position:relative}' +
      '.rl-alert{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:9px;' +
      'padding:10px 12px;font-size:13px;margin-bottom:14px;display:none}' +
      '@media (prefers-color-scheme: dark){' +
      '.rl-modal{background:#1f2937;color:#f3f4f6}.rl-sub{color:#9ca3af}.rl-label{color:#d1d5db}' +
      '.rl-input{background:#111827;border-color:#374151;color:#f3f4f6}}';
    var el = document.createElement('style');
    el.id = STYLE_ID;
    el.textContent = css;
    document.head.appendChild(el);
  }

  function close() {
    if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
    overlay = null;
    document.removeEventListener('keydown', onKey);
  }

  function onKey(e) {
    if (e.key === 'Escape') close();
  }

  function open() {
    injectStyles();
    if (overlay) return;

    overlay = document.createElement('div');
    overlay.className = 'rl-overlay';
    overlay.innerHTML =
      '<div class="rl-modal" role="dialog" aria-modal="true" aria-label="Contact form">' +
      '<button type="button" class="rl-close" aria-label="Close">&times;</button>' +
      '<h2 class="rl-title">Before we chat</h2>' +
      '<p class="rl-sub">Leave your details and we\'ll continue on WhatsApp.</p>' +
      '<div class="rl-alert"></div>' +
      '<form novalidate>' +
      '<div class="rl-field"><label class="rl-label" for="rl-name">Name</label>' +
      '<input class="rl-input" id="rl-name" name="name" type="text" autocomplete="name" required>' +
      '<div class="rl-error" data-for="name"></div></div>' +
      '<div class="rl-field"><label class="rl-label" for="rl-phone">Phone (WhatsApp)</label>' +
      '<input class="rl-input" id="rl-phone" name="phone" type="tel" autocomplete="tel" required>' +
      '<div class="rl-error" data-for="phone"></div></div>' +
      '<div class="rl-field"><label class="rl-label" for="rl-email">Email (optional)</label>' +
      '<input class="rl-input" id="rl-email" name="email" type="email" autocomplete="email">' +
      '<div class="rl-error" data-for="email"></div></div>' +
      '<button type="submit" class="rl-btn">Continue to WhatsApp</button>' +
      '</form></div>';

    document.body.appendChild(overlay);
    document.addEventListener('keydown', onKey);

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) close();
    });
    overlay.querySelector('.rl-close').addEventListener('click', close);
    overlay.querySelector('form').addEventListener('submit', submit);
    overlay.querySelector('#rl-name').focus();
  }

  function clearErrors() {
    overlay.querySelectorAll('.rl-error').forEach(function (n) {
      n.style.display = 'none';
      n.textContent = '';
    });
    overlay.querySelectorAll('.rl-input').forEach(function (n) {
      n.classList.remove('rl-invalid');
    });
    var alert = overlay.querySelector('.rl-alert');
    alert.style.display = 'none';
    alert.textContent = '';
  }

  function showFieldErrors(errors) {
    Object.keys(errors).forEach(function (field) {
      var box = overlay.querySelector('.rl-error[data-for="' + field + '"]');
      var input = overlay.querySelector('[name="' + field + '"]');
      if (box) {
        box.textContent = errors[field][0];
        box.style.display = 'block';
      }
      if (input) input.classList.add('rl-invalid');
    });
  }

  function showAlert(msg) {
    var alert = overlay.querySelector('.rl-alert');
    alert.textContent = msg;
    alert.style.display = 'block';
  }

  function submit(e) {
    e.preventDefault();
    clearErrors();

    var form = e.target;
    var btn = form.querySelector('.rl-btn');
    var payload = {
      name: form.name.value.trim(),
      phone: form.phone.value.trim(),
      email: form.email.value.trim(),
    };

    btn.disabled = true;
    btn.textContent = 'Saving…';

    fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (res) {
        return res.json().then(function (body) {
          return { status: res.status, body: body };
        });
      })
      .then(function (r) {
        if (r.status === 422 && r.body.errors) {
          showFieldErrors(r.body.errors);
          btn.disabled = false;
          btn.textContent = 'Continue to WhatsApp';
          return;
        }
        if (!r.body || !r.body.success) {
          throw new Error((r.body && r.body.message) || 'Something went wrong.');
        }
        // Saved — hand off to WhatsApp (or just close if no number configured).
        if (r.body.whatsapp_url) {
          window.location.href = r.body.whatsapp_url;
        } else {
          close();
        }
      })
      .catch(function () {
        showAlert('Could not send. Please check your connection and try again.');
        btn.disabled = false;
        btn.textContent = 'Continue to WhatsApp';
      });
  }

  // Wire up any trigger elements + expose a programmatic API.
  function bind() {
    document.querySelectorAll(TRIGGER).forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        open();
      });
    });
  }

  window.RaikanLead = { open: open, close: close };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();
