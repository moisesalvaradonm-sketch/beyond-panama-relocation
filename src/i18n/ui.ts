export const languages = {
  es: 'Español',
  en: 'English',
  fr: 'Français',
} as const;

export type Lang = keyof typeof languages;

export const defaultLang: Lang = 'es';

export const ui = {
  es: {
    // Nav
    'nav.zonas':     'Zonas para Vivir',
    'nav.visas':     'Visas y Residencia',
    'nav.costo':     'Costo de Vida',
    'nav.blog':      'Blog',
    'nav.sobre':     'Sobre Nosotros',
    'nav.contacto':  'Contacto',

    // CTAs
    'cta.quiz':      '¿Qué buscas en Panamá?',
    'cta.empezar':   'Empezar tu ruta',
    'cta.saber.mas': 'Saber más',

    // Footer
    'footer.privacidad': 'Privacidad',
    'footer.terminos':   'Términos',
    'footer.tagline':    'Información honesta para quien quiere vivir en Panamá.',
  },
  en: {
    // Nav
    'nav.zonas':     'Areas to Live',
    'nav.visas':     'Visas & Residency',
    'nav.costo':     'Cost of Living',
    'nav.blog':      'Blog',
    'nav.sobre':     'About Us',
    'nav.contacto':  'Contact',

    // CTAs
    'cta.quiz':      'What are you looking for in Panama?',
    'cta.empezar':   'Start your route',
    'cta.saber.mas': 'Learn more',

    // Footer
    'footer.privacidad': 'Privacy',
    'footer.terminos':   'Terms',
    'footer.tagline':    'Honest information for people who want to live in Panama.',
  },
  fr: {
    // Nav
    'nav.zonas':     'Régions pour Vivre',
    'nav.visas':     'Visas & Résidence',
    'nav.costo':     'Coût de la Vie',
    'nav.blog':      'Blog',
    'nav.sobre':     'À Propos',
    'nav.contacto':  'Contact',

    // CTAs
    'cta.quiz':      'Que cherchez-vous au Panama ?',
    'cta.empezar':   'Commencer votre route',
    'cta.saber.mas': 'En savoir plus',

    // Footer
    'footer.privacidad': 'Confidentialité',
    'footer.terminos':   'Conditions',
    'footer.tagline':    'Informations honnêtes pour ceux qui veulent vivre au Panama.',
  },
} as const;

export function useTranslations(lang: Lang) {
  return function t(key: keyof typeof ui[typeof defaultLang]): string {
    return (ui[lang] as Record<string, string>)[key] ?? (ui[defaultLang] as Record<string, string>)[key] ?? key;
  };
}

export function getLangFromUrl(url: URL): Lang {
  const [, lang] = url.pathname.split('/');
  if (lang in languages) return lang as Lang;
  return defaultLang;
}

export function getLocalizedPath(path: string, lang: Lang): string {
  return `/${lang}${path}`;
}
