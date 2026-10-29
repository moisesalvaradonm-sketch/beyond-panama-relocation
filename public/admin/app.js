// Beyond Panama — Admin SPA
const API = '/api/bpr';
const store = { token: localStorage.getItem('bpr_token') || '', segments: [] };

// ---------- API helper ----------
async function api(path, opts = {}) {
  const headers = { 'Content-Type': 'application/json', ...(opts.headers || {}) };
  if (store.token) headers.Authorization = 'Bearer ' + store.token;
  const res = await fetch(API + path, { ...opts, headers });
  const body = await res.json().catch(() => ({}));
  if (res.status === 401) { store.token = ''; localStorage.removeItem('bpr_token'); showLogin(); throw new Error('Unauthorised'); }
  if (!res.ok || body.ok === false) throw new Error(body.error || 'Request failed');
  return body;
}
function toast(msg, kind = 'ok') {
  const el = document.getElementById('toast');
  el.textContent = msg; el.className = kind; el.hidden = false;
  clearTimeout(el._t); el._t = setTimeout(() => el.hidden = true, 3200);
}
function el(id) { return document.getElementById(id); }
function esc(s) { return String(s ?? '').replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
function fmtDate(s) { if (!s) return '—'; return new Date(s.replace(' ','T')+'Z').toLocaleString(); }
function humanize(s) { return String(s ?? '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()); }

// ---------- Auth ----------
function showLogin() {
  el('sidebar').hidden = true;
  el('view').hidden = true;
  el('login').hidden = false;
}
function showApp() {
  el('sidebar').hidden = false;
  el('login').hidden = true;
  el('view').hidden = false;
  route(location.hash.slice(1) || 'dashboard');
}
el('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  el('loginError').hidden = true;
  try {
    const r = await api('/admin/login.php', { method: 'POST', body: JSON.stringify({ email: fd.get('email'), password: fd.get('password') }) });
    store.token = r.token; localStorage.setItem('bpr_token', store.token);
    showApp();
  } catch (err) {
    el('loginError').textContent = err.message; el('loginError').hidden = false;
  }
});
el('logout').addEventListener('click', async () => {
  try { await api('/admin/logout.php', { method: 'POST' }); } catch {}
  store.token = ''; localStorage.removeItem('bpr_token'); showLogin();
});

// ---------- Router ----------
document.querySelectorAll('#sidebar nav a').forEach(a => a.addEventListener('click', e => {
  e.preventDefault();
  location.hash = '#' + a.dataset.route;
}));
window.addEventListener('hashchange', () => route(location.hash.slice(1)));

function route(name) {
  document.querySelectorAll('#sidebar nav a').forEach(a => a.classList.toggle('active', a.dataset.route === name));
  const view = el('view'); view.innerHTML = '';
  const tpl = document.getElementById('tpl-' + name);
  if (!tpl) { view.innerHTML = '<div class="page"><h1>Not found</h1></div>'; return; }
  view.appendChild(tpl.content.cloneNode(true));
  ({ dashboard, contacts, campaigns, templates, automations, content, ai, knowledge, settings }[name] || (()=>{}))();
}

// ---------- Dashboard ----------
async function dashboard() {
  try {
    const r = await api('/admin/dashboard.php');
    const kpiWrap = el('kpis');
    const items = [
      ['Total contacts', r.stats.contacts_total],
      ['New this month', r.stats.new_this_month],
      ['Quiz leads', r.stats.quiz_leads],
      ['Newsletter subscribers', r.stats.newsletter_subs],
      ['Hot leads', r.stats.hot_leads],
      ['Consultation requests', r.stats.consultation_requests],
      ['Emails sent (30d)', r.stats.emails_sent_30d],
      ['Opens (30d)', r.stats.opens_30d],
      ['Clicks (30d)', r.stats.clicks_30d],
      ['Unsubs (30d)', r.stats.unsubs_30d],
    ];
    kpiWrap.innerHTML = items.map(([k,v]) => `<div class="kpi"><div class="label">${k}</div><div class="value">${v ?? 0}</div></div>`).join('');
    fillList(el('byInterest'), r.by_interest, x => [humanize(x.k), x.n]);
    fillList(el('bySegment'),  r.by_segment,  x => [x.name || humanize(x.k), x.n]);
    fillList(el('byQuiz'),     r.by_quiz,     x => [x.k || '—', x.n]);
    fillList(el('bySource'),   r.by_source,   x => [humanize(x.k), x.n]);
  } catch (e) { toast(e.message, 'err'); }
}
function fillList(node, rows, mapper) {
  node.innerHTML = (rows || []).map(r => { const [k,v] = mapper(r); return `<li><span>${esc(k)}</span><strong>${v}</strong></li>`; }).join('') || '<li class="muted">No data yet</li>';
}

// ---------- Contacts list ----------
let contactsPage = 1;
async function contacts() {
  const segSel = el('fSegment');
  if (!store.segments.length) {
    try {
      const s = await api('/admin/campaigns.php');
      store.segments = s.segments || [];
    } catch {}
  }
  segSel.innerHTML = '<option value="">Any segment</option>' + store.segments.map(s => `<option value="${s.slug}">${esc(s.name)}</option>`).join('');
  const load = async () => {
    const q = el('q').value.trim();
    const status = el('fStatus').value; const intent = el('fIntent').value; const seg = segSel.value;
    const params = new URLSearchParams({ page: contactsPage, per_page: 25 });
    if (q) params.set('q', q); if (status) params.set('status', status); if (intent) params.set('intent', intent); if (seg) params.set('segment', seg);
    const r = await api('/admin/contacts.php?' + params.toString());
    const rows = r.contacts.map(c => `
      <tr data-id="${c.id}">
        <td>${esc(c.email)}</td>
        <td>${esc([c.first_name,c.last_name].filter(Boolean).join(' ')) || '—'}</td>
        <td>${humanize(c.main_interest) || '—'}</td>
        <td>${humanize(c.timeline) || '—'}</td>
        <td>${c.lead_score}</td>
        <td><span class="tag ${c.lead_status}">${humanize(c.lead_status)}</span></td>
        <td>${humanize(c.intent_level)}</td>
        <td>${c.marketing_consent ? '✓' : '—'}</td>
        <td>${fmtDate(c.last_activity_at)}</td>
      </tr>`).join('');
    el('contactsTable').querySelector('tbody').innerHTML = rows || '<tr><td colspan="9" class="muted">No contacts yet.</td></tr>';
    el('contactsTable').querySelectorAll('tbody tr').forEach(tr => tr.addEventListener('click', () => { location.hash = '#contact/' + tr.dataset.id; }));

    // Pager
    const pages = Math.max(1, Math.ceil(r.total / r.per_page));
    el('pager').innerHTML = Array.from({length: pages}).map((_,i) => `<button class="${i+1===contactsPage?'primary':''}" data-p="${i+1}">${i+1}</button>`).join('');
    el('pager').querySelectorAll('button').forEach(b => b.addEventListener('click', () => { contactsPage = +b.dataset.p; load(); }));
  };
  ['q','fStatus','fIntent','fSegment'].forEach(id => el(id).addEventListener('change', () => { contactsPage = 1; load(); }));
  el('q').addEventListener('input', debounce(() => { contactsPage = 1; load(); }, 300));
  load();
}
function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t = setTimeout(()=>fn(...a), ms); }; }

// ---------- Contact detail ----------
window.addEventListener('hashchange', () => {
  const m = location.hash.match(/^#contact\/(\d+)/);
  if (m) contactDetail(+m[1]);
});
async function contactDetail(id) {
  const view = el('view'); view.innerHTML = '';
  const tpl = document.getElementById('tpl-contact'); view.appendChild(tpl.content.cloneNode(true));
  view.querySelector('[data-back]').addEventListener('click', () => history.back());

  const { contact } = await api('/admin/contacts.php?id=' + id);
  el('cTitle').textContent = (contact.first_name || contact.email) + ' · ' + humanize(contact.lead_status);
  el('cProfile').innerHTML = `
    <p><strong>${esc(contact.email)}</strong></p>
    <p class="muted">${esc(contact.first_name || '')} ${esc(contact.last_name || '')} · ${esc(humanize(contact.language))}</p>
    <p>Country: ${esc(contact.country || '—')} · City: ${esc(contact.current_city || '—')}</p>
    <p>Main interest: <strong>${humanize(contact.main_interest) || '—'}</strong></p>
    <p>Timeline: <strong>${humanize(contact.timeline) || '—'}</strong> · Budget: <strong>${humanize(contact.budget) || '—'}</strong></p>
    <p>Score: <strong>${contact.lead_score}</strong> · Status: <span class="tag ${contact.lead_status}">${humanize(contact.lead_status)}</span> · Intent: <strong>${humanize(contact.intent_level)}</strong></p>
    <p>Consent: <strong>${contact.marketing_consent ? 'Yes' : 'No'}</strong> ${contact.unsubscribed_at ? ' · Unsubscribed ' + fmtDate(contact.unsubscribed_at) : ''}</p>
    <p>Source: <strong>${esc(contact.lead_source || '—')}</strong></p>
  `;
  el('cTags').innerHTML = `
    <p><strong>Interests</strong>: ${(contact.interests||[]).map(i=>`<span class="tag">${humanize(i.interest)}</span>`).join(' ') || '—'}</p>
    <p><strong>Locations</strong>: ${(contact.locations||[]).map(i=>`<span class="tag">${humanize(i.location)}</span>`).join(' ') || '—'}</p>
    <p><strong>Segments</strong>: ${(contact.segments||[]).map(s=>`<span class="tag">${esc(s.name)}</span>`).join(' ') || '—'}</p>
  `;
  el('cQuiz').innerHTML = contact.quiz
    ? `<p><strong>${esc(contact.quiz.result_label)}</strong> — ${contact.quiz.match_percent}% match · ${fmtDate(contact.quiz.created_at)}</p>`
    : '<p class="muted">No quiz result yet.</p>';
  el('cTimeline').innerHTML = (contact.activities || []).map(a =>
    `<li><span class="at">${fmtDate(a.created_at)}</span><span class="ev">${humanize(a.event)}</span><span>${esc(a.title || a.url || a.category || '')}</span>${a.score_delta ? `<span class="muted">${a.score_delta>0?'+':''}${a.score_delta}</span>` : ''}</li>`
  ).join('') || '<li class="muted">No activity yet.</li>';

  el('aiGen').addEventListener('click', async () => {
    el('aiGen').disabled = true; el('aiResult').textContent = 'Generating…';
    try {
      const r = await api('/admin/ai.php', { method:'POST', body: JSON.stringify({ action:'generate_email', contact_id: id, goal: el('aiGoal').value }) });
      el('aiResult').innerHTML = `<strong>Subject:</strong> ${esc(r.email.subject)}\n<strong>Preheader:</strong> ${esc(r.email.preheader || '')}\n\n${esc(r.email.html)}`;
    } catch (e) { el('aiResult').textContent = e.message; }
    finally { el('aiGen').disabled = false; }
  });
}

// ---------- Campaigns ----------
async function campaigns() {
  const r = await api('/admin/campaigns.php');
  const rows = r.campaigns.map(c => `
    <tr>
      <td>${esc(c.name)}</td>
      <td>${esc(c.template_name || '—')}</td>
      <td>${esc(c.segment_slug || 'All')}</td>
      <td>${c.recurrence && c.recurrence !== 'none' ? humanize(c.recurrence) : 'One-time'}</td>
      <td><span class="tag">${humanize(c.status)}</span></td>
      <td>${c.status === 'scheduled' && c.scheduled_at ? esc(new Date(c.scheduled_at.replace(' ', 'T') + 'Z').toLocaleString('en-US', { timeZone: 'America/Panama' })) : '—'}</td>
      <td>${c.total_sent}</td><td>${c.total_opens}</td><td>${c.total_clicks}</td>
      <td>
        ${c.status === 'draft' || c.status === 'scheduled' ? `<button class="primary" data-send="${c.id}">Send now</button>` : ''}
        ${c.status === 'scheduled' ? `<button class="ghost" data-act="cancel" data-id="${c.id}">Pause</button>` : ''}
        ${c.status === 'cancelled' && c.recurrence !== 'none' ? `<button class="ghost" data-act="resume" data-id="${c.id}">Resume</button>` : ''}
      </td>
    </tr>`).join('');
  el('camTable').querySelector('tbody').innerHTML = rows || '<tr><td colspan="10" class="muted">No campaigns yet.</td></tr>';
  el('camTable').querySelectorAll('[data-send]').forEach(b => b.addEventListener('click', async () => {
    if (!confirm('Send this campaign now?')) return;
    b.disabled = true;
    try { await api('/admin/campaigns.php', { method:'POST', body: JSON.stringify({ action:'send', id: +b.dataset.send }) }); toast('Queued'); campaigns(); }
    catch (e) { toast(e.message, 'err'); }
  }));

  el('camTable').querySelectorAll('[data-act]').forEach(b => b.addEventListener('click', async () => {
    try { await api('/admin/campaigns.php', { method:'POST', body: JSON.stringify({ action: b.dataset.act, id: +b.dataset.id }) }); campaigns(); }
    catch (e) { toast(e.message, 'err'); }
  }));

  // New campaign dialog
  const dlg = el('campaignDlg');
  const recSel = dlg.querySelector('[name=recurrence]');
  const syncRec = () => dlg.querySelectorAll('[data-rec]').forEach(d => { d.hidden = d.dataset.rec !== recSel.value; });
  recSel.addEventListener('change', syncRec); syncRec();
  el('newCampaign').addEventListener('click', () => {
    const tSel = dlg.querySelector('[name=template_id]');
    tSel.innerHTML = r.templates.map(t => `<option value="${t.id}">${esc(t.name)} (${t.language})</option>`).join('');
    const sSel = dlg.querySelector('[name=segment_id]');
    sSel.innerHTML = '<option value="">All subscribers</option>' + r.segments.map(s => `<option value="${s.id}">${esc(s.name)}</option>`).join('');
    dlg.showModal();
  });
  dlg.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => dlg.close()));
  dlg.querySelector('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      await api('/admin/campaigns.php', { method:'POST', body: JSON.stringify({
        action:'create',
        name: fd.get('name'), template_id: +fd.get('template_id'),
        segment_id: fd.get('segment_id') || null,
        language: fd.get('language'),
        scheduled_at: fd.get('scheduled_at') || null,
        recurrence: fd.get('recurrence'),
        recur_day: +(fd.get('recurrence') === 'weekly' ? fd.get('weekday') : fd.get('monthday')),
        recur_time: fd.get('recurrence') === 'weekly' ? fd.get('time_w') : fd.get('time_m'),
      })});
      dlg.close(); toast('Campaign created'); campaigns();
    } catch (e) { toast(e.message, 'err'); }
  });
}

// ---------- Templates ----------
async function templates() {
  const r = await api('/admin/templates.php');
  const rows = r.templates.map(t => `
    <tr data-id="${t.id}">
      <td><code>${esc(t.slug)}</code></td>
      <td>${esc(t.name)}</td>
      <td>${t.language}</td>
      <td><span class="tag">${t.status}</span></td>
      <td>${t.ai_generated ? '✓' : ''}</td>
      <td>${fmtDate(t.updated_at)}</td>
      <td><button class="ghost" data-edit="${t.id}">Edit</button></td>
    </tr>`).join('');
  el('tplTable').querySelector('tbody').innerHTML = rows || '<tr><td colspan="7" class="muted">No templates.</td></tr>';

  const dlg = el('tplDlg'); const form = el('tplForm');
  el('newTpl').addEventListener('click', () => { form.reset(); form.id.value=''; el('tplTitle').textContent = 'New template'; dlg.showModal(); });
  el('tplTable').querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', async () => {
    const { template } = await api('/admin/templates.php?id=' + b.dataset.edit);
    for (const k of ['id','slug','name','language','status','subject','preheader','body_html']) if (form[k]) form[k].value = template[k] || '';
    el('tplTitle').textContent = 'Edit template'; dlg.showModal();
  }));
  dlg.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => dlg.close()));
  el('tplPreview').addEventListener('click', async () => {
    try {
      const r = await api('/admin/templates.php', { method:'POST', body: JSON.stringify({
        action:'preview', subject: form.subject.value, body_html: form.body_html.value
      })});
      el('tplPreviewOut').innerHTML = `<h3>${esc(r.subject)}</h3>` + r.html;
    } catch (e) { toast(e.message, 'err'); }
  });
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(form).entries());
    if (data.id) data.id = +data.id;
    try { await api('/admin/templates.php', { method:'POST', body: JSON.stringify({ action:'save', ...data }) }); toast('Saved'); dlg.close(); templates(); }
    catch (e) { toast(e.message, 'err'); }
  });
}

// ---------- Automations ----------
async function automations() {
  const r = await api('/admin/automations.php');
  el('autoList').innerHTML = r.automations.map(a => `
    <div class="card">
      <h2>${esc(a.name)} <small class="muted">· ${esc(a.slug)}</small></h2>
      <p>Trigger: <strong>${esc(a.trigger_type)}</strong> · Active enrollments: <strong>${a.active_enrollments}</strong> ·
         <label style="display:inline-block"><input type="checkbox" data-toggle="${a.id}" ${a.is_active==1?'checked':''}> Active</label></p>
      <ol>${a.steps.map(s => `<li><strong>${esc(s.step_type)}</strong> <code>${esc(s.config_json)}</code></li>`).join('')}</ol>
    </div>
  `).join('');
  el('autoList').querySelectorAll('[data-toggle]').forEach(cb => cb.addEventListener('change', async () => {
    try { await api('/admin/automations.php', { method:'POST', body: JSON.stringify({ action:'toggle', id:+cb.dataset.toggle, is_active: cb.checked }) }); toast('Saved'); }
    catch (e) { toast(e.message, 'err'); cb.checked = !cb.checked; }
  }));
}

// ---------- Content Library ----------
async function content() {
  const load = async () => {
    const s = el('cFilter').value;
    const r = await api('/admin/content.php' + (s ? '?status='+s : ''));
    const rows = r.content.map(c => `
      <tr>
        <td>${esc(c.title)}</td>
        <td>${esc(c.category || '')}</td>
        <td>${c.language}</td>
        <td><span class="tag">${c.status}</span></td>
        <td>${c.ai_generated ? '✓' : ''}</td>
        <td>${fmtDate(c.updated_at)}</td>
        <td>${c.status==='review' ? `<button class="primary" data-publish="${c.id}">Publish</button>` : ''}</td>
      </tr>`).join('');
    el('contentTable').querySelector('tbody').innerHTML = rows || '<tr><td colspan="7" class="muted">Nothing yet. Let the AI Content Engine or /api/bpr/cron/ai_content.php seed it.</td></tr>';
    el('contentTable').querySelectorAll('[data-publish]').forEach(b => b.addEventListener('click', async () => {
      try { await api('/admin/content.php', { method:'POST', body: JSON.stringify({ action:'publish', id:+b.dataset.publish }) }); toast('Published'); load(); }
      catch (e) { toast(e.message, 'err'); }
    }));
  };
  el('cFilter').addEventListener('change', load); load();
}

// ---------- AI ----------
async function ai() {
  el('aiTest').addEventListener('click', async () => {
    el('aiTestOut').textContent = 'Pinging…';
    try { const r = await api('/admin/ai.php', { method:'POST', body: JSON.stringify({ action:'test' }) });
      el('aiTestOut').textContent = JSON.stringify(r, null, 2);
    } catch (e) { el('aiTestOut').textContent = e.message; }
  });
  el('artGen').addEventListener('click', async () => {
    const topic = el('artTopic').value.trim(); if (!topic) return toast('Add a topic', 'err');
    el('artOut').textContent = 'Generating (30-60s)…';
    try {
      const r = await api('/admin/ai.php', { method:'POST', body: JSON.stringify({ action:'generate_article', topic, language: el('artLang').value }) });
      el('artOut').textContent = JSON.stringify(r.article, null, 2);
    } catch (e) { el('artOut').textContent = e.message; }
  });
}

// ---------- Knowledge Base ----------
async function knowledge() {
  const load = async () => {
    const r = await api('/admin/knowledge.php');
    el('knTable').querySelector('tbody').innerHTML = (r.knowledge || []).map(k => `
      <tr>
        <td>${esc(k.topic)}</td>
        <td><code>${esc(k.key_name)}</code></td>
        <td>${esc(k.value_text).slice(0, 200)}</td>
        <td>${esc(k.confidence)}</td>
        <td>${esc(k.verified_at || '—')}</td>
        <td><button class="ghost" data-del="${k.id}">Delete</button></td>
      </tr>`).join('') || '<tr><td colspan="6" class="muted">Empty. Add entries the AI is allowed to cite.</td></tr>';
    el('knTable').querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', async () => {
      if (!confirm('Delete this KB entry?')) return;
      await api('/admin/knowledge.php', { method:'POST', body: JSON.stringify({ action:'delete', id:+b.dataset.del }) });
      toast('Deleted'); load();
    }));
  };
  const dlg = el('knDlg'); const form = el('knForm');
  el('newKn').addEventListener('click', () => { form.reset(); dlg.showModal(); });
  dlg.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => dlg.close()));
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(form).entries());
    try { await api('/admin/knowledge.php', { method:'POST', body: JSON.stringify({ action:'save', ...data }) }); dlg.close(); toast('Saved'); load(); }
    catch (e) { toast(e.message, 'err'); }
  });
  load();
}

// ---------- Settings ----------
async function settings() {
  const r = await api('/admin/settings.php');
  const s = r.settings || {};
  const rows = Object.keys(s).sort().map(k => `
    <label>${humanize(k)}
      <input name="${esc(k)}" value="${esc(s[k])}">
    </label>`).join('');
  el('setFields').innerHTML = rows;
  el('setForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    try { await api('/admin/settings.php', { method:'POST', body: JSON.stringify({ settings: data }) }); toast('Saved'); }
    catch (e) { toast(e.message, 'err'); }
  });

  const ruleRows = (r.score_rules || []).map(rr => `
    <label>${humanize(rr.event)}
      <input type="number" name="${esc(rr.event)}" value="${rr.delta}">
    </label>`).join('');
  el('ruleFields').innerHTML = ruleRows;
  el('ruleForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const rules = [];
    fd.forEach((v, k) => rules.push({ event: k, delta: +v }));
    try { await api('/admin/settings.php', { method:'POST', body: JSON.stringify({ score_rules: rules }) }); toast('Rules saved'); }
    catch (e) { toast(e.message, 'err'); }
  });
}

// ---------- Boot ----------
if (store.token) showApp(); else showLogin();
