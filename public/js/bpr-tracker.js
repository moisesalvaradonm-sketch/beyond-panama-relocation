/*!
 * Beyond Panama — lightweight tracking widget.
 * Drop into every page:
 *   <script src="/js/bpr-tracker.js" defer></script>
 *
 * Auto-tracks:
 *   - one page_view per pageload
 *   - page_view_residency / _property / _business / _retirement when URL matches
 *   - whatsapp_click on any <a href="wa.me/..."> or [data-whatsapp]
 *   - consultation_request on any [data-consultation]
 *   - guide_download on <a href$=".pdf"> or [data-guide]
 * Attribution:
 *   - reads bpr_cid cookie for contact id (set when the person submits the quiz)
 *   - persists utm_* for 30 days
 */
(function () {
  var API = '/api/bpr/routes/track.php';
  var COOKIE = 'bpr_cid';
  var UTM = 'bpr_utm';

  function get(name) {
    return document.cookie.split('; ').reduce(function (a, c) {
      var p = c.split('='); return p[0] === name ? decodeURIComponent(p[1] || '') : a;
    }, '');
  }
  function set(name, val, days) {
    var d = new Date(Date.now() + days*864e5);
    document.cookie = name + '=' + encodeURIComponent(val) + ';path=/;expires=' + d.toUTCString() + ';SameSite=Lax';
  }
  // Mirror key events to GA4 (gtag exists once consent is given; otherwise a no-op queue)
  var GA_EVENTS = { whatsapp_click: 'whatsapp_click', consultation_request: 'consultation_request', guide_download: 'guide_download', quiz_complete: 'quiz_complete', webinar_registration: 'webinar_registration', lead: 'generate_lead' };
  function ga(payload) {
    try {
      var name = GA_EVENTS[payload.event];
      if (name && typeof window.gtag === 'function') window.gtag('event', name, payload.meta || {});
    } catch (e) {}
  }
  function post(payload) {
    ga(payload);
    try {
      payload.contact_id = payload.contact_id || (+get(COOKIE) || null);
      payload.url = location.href;
      payload.title = document.title;
      var body = JSON.stringify(payload);
      if (navigator.sendBeacon) navigator.sendBeacon(API, new Blob([body], { type: 'application/json' }));
      else fetch(API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: body, keepalive: true }).catch(function(){});
    } catch (e) {}
  }

  // Capture UTM once
  var qp = new URLSearchParams(location.search);
  var utm = {};
  ['utm_source','utm_medium','utm_campaign','utm_content','utm_term'].forEach(function (k) {
    var v = qp.get(k); if (v) utm[k] = v;
  });
  if (Object.keys(utm).length) set(UTM, JSON.stringify(utm), 30);

  // Page view classification
  var path = location.pathname.toLowerCase();
  var evt = 'page_view';
  if (/residency|visa|pensionado|immigration/.test(path)) evt = 'page_view_residency';
  else if (/property|real-estate|homes|houses/.test(path)) evt = 'page_view_property';
  else if (/business|entrepreneur|remote/.test(path)) evt = 'page_view_business';
  else if (/retirement|pensionado|retire/.test(path)) evt = 'page_view_retirement';

  post({ event: evt });

  // Delegated click tracking
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a,[data-whatsapp],[data-consultation],[data-guide]');
    if (!a) return;
    var href = (a.getAttribute('href') || '').toLowerCase();
    if (a.dataset.whatsapp || /wa\.me|whatsapp\.com/.test(href)) post({ event: 'whatsapp_click', meta: { href: href } });
    if (a.dataset.consultation) post({ event: 'consultation_request', meta: { href: href } });
    if (a.dataset.guide || /\.pdf(\?|$)/.test(href)) post({ event: 'guide_download', meta: { href: href } });
  }, true);

  // Expose a tiny SDK for the site to attach identity after form submit / quiz result
  window.BPR = {
    identify: function (contactId) { if (contactId) set(COOKIE, contactId, 365); },
    track: post,
    utm: function () { try { return JSON.parse(get(UTM) || '{}'); } catch { return {}; } }
  };
})();
