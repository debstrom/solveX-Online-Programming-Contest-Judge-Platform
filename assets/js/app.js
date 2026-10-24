(function () {
  // Tab key inserts spaces inside the code editor
  document.querySelectorAll('textarea.code-input').forEach(function (ta) {
    ta.addEventListener('keydown', function (ev) {
      if (ev.key === 'Tab') {
        ev.preventDefault();
        var s = ta.selectionStart, e = ta.selectionEnd;
        ta.value = ta.value.substring(0, s) + '    ' + ta.value.substring(e);
        ta.selectionStart = ta.selectionEnd = s + 4;
      }
    });
  });

  // Countdown timers: <span class="countdown" data-seconds="123" data-reload="1">
  document.querySelectorAll('[data-seconds]').forEach(function (el) {
    var left = parseInt(el.dataset.seconds, 10);
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function tick() {
      if (left <= 0) { el.textContent = '00:00:00'; if (el.dataset.reload) setTimeout(function () { location.reload(); }, 1000); return; }
      var d = Math.floor(left / 86400), h = Math.floor((left % 86400) / 3600), m = Math.floor((left % 3600) / 60), s = left % 60;
      el.textContent = (d > 0 ? d + 'd ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
      left--; setTimeout(tick, 1000);
    }
    tick();
  });

  // Live verdict polling: <div id="verdict-box" data-poll="URL">
  var box = document.getElementById('verdict-box');
  if (box && box.dataset.poll) {
    (function poll() {
      fetch(box.dataset.poll, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
        if (d.pending) { setTimeout(poll, 1500); } else { location.reload(); }
      }).catch(function () { setTimeout(poll, 3000); });
    })();
  }

  // Auto-refresh leaderboard
  var lb = document.getElementById('lb-refresh');
  if (lb) setTimeout(function () { location.reload(); }, 30000);

  // Confirm dialogs
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (ev) { if (!confirm(el.dataset.confirm)) ev.preventDefault(); });
  });
})();
