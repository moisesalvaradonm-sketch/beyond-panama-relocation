import { useState } from 'react';

interface Props {
  lang?: 'en' | 'es' | 'fr';
}

const t = {
  en: {
    name: 'Your name',
    email: 'Email address',
    phone: 'Phone / WhatsApp (optional)',
    country: 'Country of origin (optional)',
    message: 'Your message or question',
    messagePlaceholder: 'Tell us a bit about your situation — what you\'re planning, your timeline, your main questions...',
    submit: 'Send message',
    sending: 'Sending…',
    successTitle: 'Message received!',
    successText: 'We\'ll get back to you within 24 hours — usually much sooner.',
    errorText: 'Something went wrong. Please try again or write us directly at contact@beyondpanamarelocation.com',
    required: 'Required field',
  },
  es: {
    name: 'Tu nombre',
    email: 'Correo electrónico',
    phone: 'Teléfono / WhatsApp (opcional)',
    country: 'País de origen (opcional)',
    message: 'Tu mensaje o pregunta',
    messagePlaceholder: 'Cuéntanos un poco sobre tu situación — qué estás planeando, tu cronograma, tus principales preguntas...',
    submit: 'Enviar mensaje',
    sending: 'Enviando…',
    successTitle: '¡Mensaje recibido!',
    successText: 'Te responderemos en menos de 24 horas — normalmente mucho antes.',
    errorText: 'Algo salió mal. Intenta de nuevo o escríbenos directamente a contact@beyondpanamarelocation.com',
    required: 'Campo obligatorio',
  },
  fr: {
    name: 'Votre nom',
    email: 'Adresse e-mail',
    phone: 'Téléphone / WhatsApp (optionnel)',
    country: 'Pays d\'origine (optionnel)',
    message: 'Votre message ou question',
    messagePlaceholder: 'Parlez-nous un peu de votre situation — ce que vous planifiez, votre calendrier, vos principales questions...',
    submit: 'Envoyer le message',
    sending: 'Envoi en cours…',
    successTitle: 'Message reçu !',
    successText: 'Nous vous répondrons dans les 24 heures — généralement bien plus tôt.',
    errorText: 'Une erreur s\'est produite. Réessayez ou écrivez-nous directement à contact@beyondpanamarelocation.com',
    required: 'Champ obligatoire',
  },
};

export default function ContactForm({ lang = 'en' }: Props) {
  const tx = t[lang];
  const [form, setForm] = useState({ name: '', email: '', phone: '', country: '', message: '' });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [status, setStatus] = useState<'idle' | 'sending' | 'success' | 'error'>('idle');

  function validate() {
    const e: Record<string, string> = {};
    if (!form.name.trim()) e.name = tx.required;
    if (!form.email.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) e.email = tx.required;
    if (!form.message.trim()) e.message = tx.required;
    return e;
  }

  async function handleSubmit(ev: React.FormEvent) {
    ev.preventDefault();
    const e = validate();
    if (Object.keys(e).length) { setErrors(e); return; }
    setErrors({});
    setStatus('sending');

    const apiKey = import.meta.env.PUBLIC_BREVO_API_KEY;
    const html = `
      <h2>New contact from Beyond Panama website</h2>
      <p><strong>Name:</strong> ${form.name}</p>
      <p><strong>Email:</strong> ${form.email}</p>
      ${form.phone ? `<p><strong>Phone/WhatsApp:</strong> ${form.phone}</p>` : ''}
      ${form.country ? `<p><strong>Country of origin:</strong> ${form.country}</p>` : ''}
      <p><strong>Language:</strong> ${lang.toUpperCase()}</p>
      <hr/>
      <p><strong>Message:</strong></p>
      <p>${form.message.replace(/\n/g, '<br/>')}</p>
    `;

    try {
      const res = await fetch('https://api.brevo.com/v3/smtp/email', {
        method: 'POST',
        headers: { 'api-key': apiKey, 'Content-Type': 'application/json' },
        body: JSON.stringify({
          sender: { name: 'Beyond Panama Website', email: 'contact@beyondpanamarelocation.com' },
          to: [{ email: 'contact@beyondpanamarelocation.com', name: 'Beyond Panama' }],
          replyTo: { email: form.email, name: form.name },
          subject: `Contact from website — ${form.name}`,
          htmlContent: html,
        }),
      });
      if (res.ok || res.status === 201) {
        setStatus('success');
        setForm({ name: '', email: '', phone: '', country: '', message: '' });
      } else {
        setStatus('error');
      }
    } catch {
      setStatus('error');
    }
  }

  if (status === 'success') {
    return (
      <div className="rounded-2xl bg-jade/5 border border-jade/25 p-10 text-center">
        <div className="w-14 h-14 rounded-full bg-jade/10 flex items-center justify-center mx-auto mb-4">
          <svg className="w-7 h-7 text-jade" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <p className="font-serif text-2xl text-slate mb-2">{tx.successTitle}</p>
        <p className="text-slate-mid text-sm">{tx.successText}</p>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} noValidate className="space-y-5">
      {/* Name + Email */}
      <div className="grid sm:grid-cols-2 gap-5">
        <div>
          <input
            type="text"
            placeholder={tx.name}
            value={form.name}
            onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
            className={`w-full px-4 py-3 rounded-xl border text-slate text-sm placeholder:text-slate-mid/60 bg-white focus:outline-none focus:ring-2 focus:ring-jade/30 transition
              ${errors.name ? 'border-red-400 focus:border-red-400' : 'border-jade/20 focus:border-jade'}`}
          />
          {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
        </div>
        <div>
          <input
            type="email"
            placeholder={tx.email}
            value={form.email}
            onChange={e => setForm(f => ({ ...f, email: e.target.value }))}
            className={`w-full px-4 py-3 rounded-xl border text-slate text-sm placeholder:text-slate-mid/60 bg-white focus:outline-none focus:ring-2 focus:ring-jade/30 transition
              ${errors.email ? 'border-red-400 focus:border-red-400' : 'border-jade/20 focus:border-jade'}`}
          />
          {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email}</p>}
        </div>
      </div>

      {/* Phone + Country */}
      <div className="grid sm:grid-cols-2 gap-5">
        <input
          type="tel"
          placeholder={tx.phone}
          value={form.phone}
          onChange={e => setForm(f => ({ ...f, phone: e.target.value }))}
          className="w-full px-4 py-3 rounded-xl border border-jade/20 text-slate text-sm placeholder:text-slate-mid/60 bg-white focus:outline-none focus:border-jade focus:ring-2 focus:ring-jade/30 transition"
        />
        <input
          type="text"
          placeholder={tx.country}
          value={form.country}
          onChange={e => setForm(f => ({ ...f, country: e.target.value }))}
          className="w-full px-4 py-3 rounded-xl border border-jade/20 text-slate text-sm placeholder:text-slate-mid/60 bg-white focus:outline-none focus:border-jade focus:ring-2 focus:ring-jade/30 transition"
        />
      </div>

      {/* Message */}
      <div>
        <textarea
          rows={5}
          placeholder={tx.messagePlaceholder}
          value={form.message}
          onChange={e => setForm(f => ({ ...f, message: e.target.value }))}
          className={`w-full px-4 py-3 rounded-xl border text-slate text-sm placeholder:text-slate-mid/60 bg-white focus:outline-none focus:ring-2 focus:ring-jade/30 transition resize-none
            ${errors.message ? 'border-red-400 focus:border-red-400' : 'border-jade/20 focus:border-jade'}`}
        />
        {errors.message && <p className="text-red-500 text-xs mt-1">{errors.message}</p>}
      </div>

      {status === 'error' && (
        <p className="text-red-500 text-sm">{tx.errorText}</p>
      )}

      <button
        type="submit"
        disabled={status === 'sending'}
        className="w-full sm:w-auto px-8 py-3.5 rounded-full bg-jade text-white font-medium text-sm hover:bg-jade-dark transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
      >
        {status === 'sending' ? tx.sending : tx.submit}
      </button>
    </form>
  );
}
