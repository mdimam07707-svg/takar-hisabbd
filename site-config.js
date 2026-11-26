/* অ্যাডমিন প্যানেলের ঘোষণা ও রক্ষণাবেক্ষণ মোড অ্যাপে দেখায়।
   /api/config.php না পেলে চুপচাপ কিছুই করে না, অ্যাপ স্বাভাবিক চলে। */
(function () {
  'use strict';
  fetch('/api/config.php', { cache: 'no-store', credentials: 'same-origin' })
    .then(function (r) { return r.ok ? r.json() : null; })
    .then(function (c) {
      if (!c || typeof c !== 'object') return;

      if (c.app_name) {
        try { document.title = String(c.app_name); } catch (e) {}
      }

      if (c.maintenance) {
        var ov = document.createElement('div');
        ov.style.cssText = 'position:fixed;inset:0;z-index:2147483647;background:#090a0d;color:#eceef3;display:grid;place-items:center;padding:24px;text-align:center;font:18px/1.7 "Hind Siliguri",system-ui,sans-serif';
        var p = document.createElement('p');
        p.style.maxWidth = '520px';
        p.textContent = String(c.maintenance_message || '');
        ov.appendChild(p);
        document.body.appendChild(ov);
        return;
      }

      var msg = c.announcement ? String(c.announcement) : '';
      if (!msg) return;
      try { if (sessionStorage.getItem('hd_ann') === msg) return; } catch (e) {}

      var bar = document.createElement('div');
      bar.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:2147483000;background:#c4f135;color:#111;padding:10px 44px 10px 16px;font:500 14px/1.5 "Hind Siliguri",system-ui,sans-serif;text-align:center';
      bar.setAttribute('role', 'status');
      bar.appendChild(document.createTextNode(msg));
      var x = document.createElement('button');
      x.type = 'button';
      x.setAttribute('aria-label', 'বন্ধ করুন');
      x.textContent = '×';
      x.style.cssText = 'position:absolute;right:10px;top:50%;transform:translateY(-50%);border:0;background:transparent;font-size:22px;line-height:1;cursor:pointer;color:#111';
      x.onclick = function () {
        try { sessionStorage.setItem('hd_ann', msg); } catch (e) {}
        bar.remove();
      };
      bar.appendChild(x);
      document.body.appendChild(bar);
    })
    .catch(function () {});
})();
