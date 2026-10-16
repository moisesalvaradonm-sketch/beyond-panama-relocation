import { useState } from 'react';

interface Props {
  /** Shown on the success screen, e.g. "Thursday, March 12 · 7:00 PM EST". */
  whenLabel?: string;
  buttonLabel?: string;
}

const TIMELINES = [
  { value: 'researching', label: 'Just researching' },
  { value: '2plus_years', label: 'More than 2 years' },
  { value: '1_2_years', label: '1–2 years' },
  { value: '6_12_months', label: '6–12 months' },
  { value: '3_6_months', label: '3–6 months' },
  { value: 'under_3_months', label: 'Less than 3 months' },
];

const INTERESTS = [
  { value: 'residency', label: 'Visas & residency' },
  { value: 'property', label: 'Buying or renting a home' },
  { value: 'retirement', label: 'Retiring in Panama' },
  { value: 'business', label: 'Working remotely or starting a business' },
  { value: 'relocation', label: 'The full picture — still exploring' },
];

const field =
  'w-full px-4 py-3 rounded-xl border bg-white text-slate text-sm placeholder:text-slate-mid/60 focus:outline-none focus:ring-2 focus:ring-jade/30 transition';

export default function WebinarRegistrationForm({
  whenLabel,
  buttonLabel = 'Save my seat',
}: Props) {
  const [form, setForm] = useState({
    name: '',
    email: '',
    interest: '',
    timeline: '',
    question: '',
    consent: false,
    website: '', // honeypot
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [status, setStatus] = useState<'idle' | 'sending' | 'success' | 'error'>('idle');

  function validate() {
    const e: Record<string, string> = {};
    if (!form.name.trim()) e.name = 'Please enter your name.';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) e.email = 'Please enter a valid email.';
    if (!form.consent) e.consent = 'Please confirm so we can send you the details.';
    return e;
  }

  async function handleSubmit(ev: React.FormEvent) {
    ev.preventDefault();
    const e = validate();
    setErrors(e);
    if (Object.keys(e).length) return;
    setStatus('sending');

    const utm = (() => {
      try {
        return JSON.parse(
          decodeURIComponent(
            document.cookie.split('; ').find(c => c.startsWith('bpr_utm='))?.split('=')[1] || '%7B%7D',
          ),
        );
      } catch {
        return {};
      }
    })();
    const [first_name, ...rest] = form.name.trim().split(/\s+/);

    try {
      const res = await fetch('/api/bpr/routes/form.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          form_key: 'webinar',
          email: form.email,
          first_name,
          last_name: rest.join(' '),
          language: 'en',
          main_interest: form.interest || 'relocation',
          interests: form.interest ? [form.interest] : ['relocation'],
          timeline: form.timeline || null,
          message: form.question,
          consent: true,
          website: form.website,
          utm_source: utm.utm_source || '',
          utm_medium: utm.utm_medium || '',
          utm_campaign: utm.utm_campaign || '',
          utm_content: utm.utm_content || '',
        }),
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error('request failed');
      if (json?.contact_id) {
        document.cookie = `bpr_cid=${json.contact_id};path=/;max-age=${60 * 60 * 24 * 365};SameSite=Lax`;
      }
      (window as any).gtag?.('event', 'generate_lead', { event_category: 'webinar' });
      setStatus('success');
    } catch {
      setStatus('error');
    }
  }

  if (status === 'success') {
    return (
      <div role="status" className="rounded-2xl bg-white border border-jade/25 p-8 text-center">
        <div className="w-14 h-14 rounded-full bg-jade/10 flex items-center justify-center mx-auto mb-4">
          <svg className="w-7 h-7 text-jade" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <p className="font-serif text-2xl text-slate mb-2">You're registered</p>
        {whenLabel && <p className="text-slate font-medium text-sm mb-2">{whenLabel}</p>}
        <p className="text-slate-mid text-sm">
          We'll email the joining link and a reminder before we start. If you submitted a question, we'll try to cover it live.
        </p>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} noValidate className="space-y-4" aria-label="Webinar registration">
      <div>
        <label htmlFor="wb-name" className="block text-xs font-semibold text-slate mb-1.5">Your name</label>
        <input
          id="wb-name"
          type="text"
          autoComplete="name"
          value={form.name}
          onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
          className={`${field} ${errors.name ? 'border-red-400' : 'border-jade/20 focus:border-jade'}`}
        />
        {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
      </div>

      <div>
        <label htmlFor="wb-email" className="block text-xs font-semibold text-slate mb-1.5">Email address</label>
        <input
          id="wb-email"
          type="email"
          autoComplete="email"
          value={form.email}
          onChange={e => setForm(f => ({ ...f, email: e.target.value }))}
          className={`${field} ${errors.email ? 'border-red-400' : 'border-jade/20 focus:border-jade'}`}
        />
        {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email}</p>}
      </div>

      <div className="grid sm:grid-cols-2 gap-4">
        <div>
          <label htmlFor="wb-interest" className="block text-xs font-semibold text-slate mb-1.5">
            What interests you most? <span className="font-normal text-slate-mid">(optional)</span>
          </label>
          <select
            id="wb-interest"
            value={form.interest}
            onChange={e => setForm(f => ({ ...f, interest: e.target.value }))}
            className={`${field} border-jade/20 focus:border-jade`}
          >
            <option value="">Select one</option>
            {INTERESTS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
          </select>
        </div>
        <div>
          <label htmlFor="wb-timeline" className="block text-xs font-semibold text-slate mb-1.5">
            Your timeframe <span className="font-normal text-slate-mid">(optional)</span>
          </label>
          <select
            id="wb-timeline"
            value={form.timeline}
            onChange={e => setForm(f => ({ ...f, timeline: e.target.value }))}
            className={`${field} border-jade/20 focus:border-jade`}
          >
            <option value="">Select one</option>
            {TIMELINES.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
          </select>
        </div>
      </div>

      <div>
        <label htmlFor="wb-question" className="block text-xs font-semibold text-slate mb-1.5">
          A question you'd like answered <span className="font-normal text-slate-mid">(optional)</span>
        </label>
        <textarea
          id="wb-question"
          rows={3}
          value={form.question}
          onChange={e => setForm(f => ({ ...f, question: e.target.value }))}
          className={`${field} border-jade/20 focus:border-jade resize-none`}
        />
      </div>

      {/* Honeypot — hidden from people, filled by bots */}
      <div aria-hidden="true" style={{ position: 'absolute', left: '-9999px' }}>
        <label>
          Website
          <input
            type="text"
            tabIndex={-1}
            autoComplete="off"
            value={form.website}
            onChange={e => setForm(f => ({ ...f, website: e.target.value }))}
          />
        </label>
      </div>

      <div>
        <label className="flex items-start gap-3 text-xs text-slate-mid leading-relaxed cursor-pointer">
          <input
            type="checkbox"
            checked={form.consent}
            onChange={e => setForm(f => ({ ...f, consent: e.target.checked }))}
            className="mt-0.5 h-4 w-4 rounded border-jade/30 accent-[#1A6B9E] shrink-0"
          />
          <span>
            Email me the webinar details and a recording. I can unsubscribe any time. See our{' '}
            <a href="/en/privacy" className="text-jade underline">privacy policy</a>.
          </span>
        </label>
        {errors.consent && <p className="text-red-500 text-xs mt-1">{errors.consent}</p>}
      </div>

      {status === 'error' && (
        <p role="alert" className="text-red-500 text-sm">
          Something went wrong. Please try again, or write to contact@beyondpanamarelocation.com.
        </p>
      )}

      <button
        type="submit"
        disabled={status === 'sending'}
        className="w-full px-8 py-3.5 rounded-full bg-jade text-white font-medium text-sm hover:bg-jade-dark transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
      >
        {status === 'sending' ? 'Registering…' : buttonLabel}
      </button>
      <p className="text-center text-xs text-slate-mid">Free. No payment details needed.</p>
    </form>
  );
}
