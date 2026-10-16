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
    'nav.zonas':         'Zonas para Vivir',
    'nav.visas':         'Visas y Residencia',
    'nav.costo':         'Costo de Vida',
    'nav.blog':          'Blog',
    'nav.sobre':         'Sobre Nosotros',
    'nav.contacto':      'Contacto',
    'nav.realestate':    'Propiedades',
    'nav.insights':      'Beyond Insights',

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
    'nav.zonas':         'Areas to Live',
    'nav.visas':         'Visas & Residency',
    'nav.costo':         'Cost of Living',
    'nav.blog':          'Blog',
    'nav.sobre':         'About Us',
    'nav.contacto':      'Contact',
    'nav.realestate':    'Properties',
    'nav.insights':      'Beyond Insights',

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
    'nav.zonas':         'Régions pour Vivre',
    'nav.visas':         'Visas & Résidence',
    'nav.costo':         'Coût de la Vie',
    'nav.blog':          'Blog',
    'nav.sobre':         'À Propos',
    'nav.contacto':      'Contact',
    'nav.realestate':    'Propriétés',
    'nav.insights':      'Beyond Insights',

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
  const full = `/${lang}${path}`;
  // Canonical URLs end with a slash (trailingSlash: 'always'); avoids a 301 hop per link.
  return /[?#.]/.test(full.split('/').pop() ?? '') || full.endsWith('/') ? full : `${full}/`;
}

// Segment-level path translation table (keyed by any-language value)
const pathSegments: Record<string, Record<Lang, string>> = {
  // Main sections
  'areas':                    { en: 'areas',                    es: 'zonas',                    fr: 'zones' },
  'zonas':                    { en: 'areas',                    es: 'zonas',                    fr: 'zones' },
  'zones':                    { en: 'areas',                    es: 'zonas',                    fr: 'zones' },
  'real-estate':              { en: 'real-estate',              es: 'bienes-raices',            fr: 'immobilier' },
  'bienes-raices':            { en: 'real-estate',              es: 'bienes-raices',            fr: 'immobilier' },
  'immobilier':               { en: 'real-estate',              es: 'bienes-raices',            fr: 'immobilier' },
  'cost-of-living':           { en: 'cost-of-living',           es: 'costo-de-vida',            fr: 'cout-de-la-vie' },
  'costo-de-vida':            { en: 'cost-of-living',           es: 'costo-de-vida',            fr: 'cout-de-la-vie' },
  'cout-de-la-vie':           { en: 'cost-of-living',           es: 'costo-de-vida',            fr: 'cout-de-la-vie' },
  'healthcare':               { en: 'healthcare',               es: 'salud-seguros',            fr: 'sante-assurances' },
  'salud-seguros':            { en: 'healthcare',               es: 'salud-seguros',            fr: 'sante-assurances' },
  'sante-assurances':         { en: 'healthcare',               es: 'salud-seguros',            fr: 'sante-assurances' },
  'banking':                  { en: 'banking',                  es: 'banca-finanzas',           fr: 'banque-finances' },
  'banca-finanzas':           { en: 'banking',                  es: 'banca-finanzas',           fr: 'banque-finances' },
  'banque-finances':          { en: 'banking',                  es: 'banca-finanzas',           fr: 'banque-finances' },
  'geography-climate':        { en: 'geography-climate',        es: 'geografia-clima',          fr: 'geographie-climat' },
  'geografia-clima':          { en: 'geography-climate',        es: 'geografia-clima',          fr: 'geographie-climat' },
  'geographie-climat':        { en: 'geography-climate',        es: 'geografia-clima',          fr: 'geographie-climat' },
  'education-activities':     { en: 'education-activities',     es: 'educacion-actividades',    fr: 'education-activites' },
  'educacion-actividades':    { en: 'education-activities',     es: 'educacion-actividades',    fr: 'education-activites' },
  'education-activites':      { en: 'education-activities',     es: 'educacion-actividades',    fr: 'education-activites' },
  'working-in-panama':        { en: 'working-in-panama',        es: 'trabajar-en-panama',       fr: 'travailler-panama' },
  'trabajar-en-panama':       { en: 'working-in-panama',        es: 'trabajar-en-panama',       fr: 'travailler-panama' },
  'travailler-panama':        { en: 'working-in-panama',        es: 'trabajar-en-panama',       fr: 'travailler-panama' },
  'pensionado-benefits':      { en: 'pensionado-benefits',      es: 'beneficios-pensionado',    fr: 'avantages-pensionado' },
  'beneficios-pensionado':    { en: 'pensionado-benefits',      es: 'beneficios-pensionado',    fr: 'avantages-pensionado' },
  'avantages-pensionado':     { en: 'pensionado-benefits',      es: 'beneficios-pensionado',    fr: 'avantages-pensionado' },
  'next-steps':               { en: 'next-steps',               es: 'proximos-pasos',           fr: 'prochaines-etapes' },
  'proximos-pasos':           { en: 'next-steps',               es: 'proximos-pasos',           fr: 'prochaines-etapes' },
  'prochaines-etapes':        { en: 'next-steps',               es: 'proximos-pasos',           fr: 'prochaines-etapes' },
  'about':                    { en: 'about',                    es: 'sobre-nosotros',           fr: 'a-propos' },
  'sobre-nosotros':           { en: 'about',                    es: 'sobre-nosotros',           fr: 'a-propos' },
  'a-propos':                 { en: 'about',                    es: 'sobre-nosotros',           fr: 'a-propos' },
  'contact':                  { en: 'contact',                  es: 'contacto',                 fr: 'contact' },
  'contacto':                 { en: 'contact',                  es: 'contacto',                 fr: 'contact' },
  'privacy':                  { en: 'privacy',                  es: 'privacidad',               fr: 'confidentialite' },
  'privacidad':               { en: 'privacy',                  es: 'privacidad',               fr: 'confidentialite' },
  'confidentialite':          { en: 'privacy',                  es: 'privacidad',               fr: 'confidentialite' },
  'terms':                    { en: 'terms',                    es: 'terminos',                 fr: 'conditions' },
  'terminos':                 { en: 'terms',                    es: 'terminos',                 fr: 'conditions' },
  'conditions':               { en: 'terms',                    es: 'terminos',                 fr: 'conditions' },
  'whats-new-2025':           { en: 'whats-new-2025',           es: 'novedades-2025',           fr: 'nouveautes-2025' },
  'novedades-2025':           { en: 'whats-new-2025',           es: 'novedades-2025',           fr: 'nouveautes-2025' },
  'nouveautes-2025':          { en: 'whats-new-2025',           es: 'novedades-2025',           fr: 'nouveautes-2025' },
  'testimonials':             { en: 'testimonials',             es: 'testimonios',              fr: 'temoignages' },
  'testimonios':              { en: 'testimonials',             es: 'testimonios',              fr: 'temoignages' },
  'temoignages':              { en: 'testimonials',             es: 'testimonios',              fr: 'temoignages' },
  'transport':                { en: 'transport',                es: 'transporte',               fr: 'transport' },
  'transporte':               { en: 'transport',                es: 'transporte',               fr: 'transport' },
  'communications':           { en: 'communications',           es: 'comunicaciones',           fr: 'communications' },
  'comunicaciones':           { en: 'communications',           es: 'comunicaciones',           fr: 'communications' },
  'company-formation':        { en: 'company-formation',        es: 'constitucion-empresas',    fr: 'creation-societe' },
  'constitucion-empresas':    { en: 'company-formation',        es: 'constitucion-empresas',    fr: 'creation-societe' },
  'creation-societe':         { en: 'company-formation',        es: 'constitucion-empresas',    fr: 'creation-societe' },
  // Visa sub-paths
  'comparison':               { en: 'comparison',               es: 'comparativa',              fr: 'comparaison' },
  'comparativa':              { en: 'comparison',               es: 'comparativa',              fr: 'comparaison' },
  'comparaison':              { en: 'comparison',               es: 'comparativa',              fr: 'comparaison' },
  'friendly-nations':         { en: 'friendly-nations',         es: 'naciones-amigas',          fr: 'nations-amies' },
  'naciones-amigas':          { en: 'friendly-nations',         es: 'naciones-amigas',          fr: 'nations-amies' },
  'nations-amies':            { en: 'friendly-nations',         es: 'naciones-amigas',          fr: 'nations-amies' },
  'digital-nomad':            { en: 'digital-nomad',            es: 'nomada-digital',           fr: 'nomade-digital' },
  'nomada-digital':           { en: 'digital-nomad',            es: 'nomada-digital',           fr: 'nomade-digital' },
  'nomade-digital':           { en: 'digital-nomad',            es: 'nomada-digital',           fr: 'nomade-digital' },
  'reforestation':            { en: 'reforestation',            es: 'reforestacion',            fr: 'reboisement' },
  'reforestacion':            { en: 'reforestation',            es: 'reforestacion',            fr: 'reboisement' },
  'reboisement':              { en: 'reforestation',            es: 'reforestacion',            fr: 'reboisement' },
  'qualified-investor':       { en: 'qualified-investor',       es: 'inversionista-calificado', fr: 'investisseur-qualifie' },
  'inversionista-calificado': { en: 'qualified-investor',       es: 'inversionista-calificado', fr: 'investisseur-qualifie' },
  'investisseur-qualifie':    { en: 'qualified-investor',       es: 'inversionista-calificado', fr: 'investisseur-qualifie' },
  // Panama City area name (different in each language)
  'panama-city':              { en: 'panama-city',              es: 'ciudad-de-panama',         fr: 'ville-de-panama' },
  'ciudad-de-panama':         { en: 'panama-city',              es: 'ciudad-de-panama',         fr: 'ville-de-panama' },
  'ville-de-panama':          { en: 'panama-city',              es: 'ciudad-de-panama',         fr: 'ville-de-panama' },
};

// Blog slug cross-language map. A post with no entry for a language has NO translation
// there (hreflang must not point to the blog index in that case).
const blogPairs: Array<Partial<Record<Lang, string>>> = [
  { en: 'pensionado-visa-guide',           es: 'visa-pensionado-guia' },
  { en: 'friendly-nations-visa-guide',     es: 'visa-naciones-amigas-guia' },
  { en: 'banking-in-panama-guide',         es: 'banca-panama-extranjeros' },
  { en: 'real-cost-of-living-panama',      es: 'costo-real-vivir-panama' },
  { en: 'boquete-vs-coronado',             es: 'boquete-vs-coronado' },
  { en: 'panama-city-neighborhoods-guide', es: 'guia-barrios-ciudad-panama' },
  { en: 'qualified-investor-decree-2026',  es: 'inversionista-calificado-decreto-2026', fr: 'investisseur-qualifie-decret-2026' },
  { en: 'panama-territorial-tax-guide',    es: 'ventajas-fiscales-panama-expatriados' },
  { en: 'healthcare-in-panama-for-expats', es: 'salud-panama-extranjeros' },
  { en: 'internet-and-remote-work-in-panama', es: 'internet-trabajo-remoto-panama' },
  { en: 'renting-apartment-panama-guide',  es: 'alquilar-apartamento-panama-extranjero' },
  { en: 'why-panama-attracts-retirees',    es: 'por-que-panama-atrae-jubilados', fr: 'pourquoi-panama-attire-retraites' },
];
const insightPairs: Array<Partial<Record<Lang, string>>> = [
  { en: 'us-documents-before-moving-to-panama', es: 'documentos-eeuu-antes-de-mudarte-panama' },
];

function findPair(pairs: Array<Partial<Record<Lang, string>>>, slug: string) {
  return pairs.find(p => Object.values(p).includes(slug));
}

/**
 * Strict translation for hreflang: returns the equivalent path in `toLang`,
 * or null when no real translation exists (never falls back to an index page).
 * Existence of the target route is checked by the caller.
 */
export function translatePathStrict(path: string, fromLang: Lang, toLang: Lang): string | null {
  if (fromLang === toLang) return path;
  const parts = path.split('/').filter(Boolean);
  if (parts.length === 0) return '/';
  if (parts[0] === '404') return null;
  if ((parts[0] === 'blog' || parts[0] === 'insights') && parts[1]) {
    const pair = findPair(parts[0] === 'blog' ? blogPairs : insightPairs, parts[1]);
    const slug = pair?.[toLang];
    return slug ? `/${parts[0]}/${slug}/` : null;
  }
  const translated = parts.map(seg => pathSegments[seg]?.[toLang] ?? seg);
  return '/' + translated.join('/') + '/';
}

export function translatePath(path: string, fromLang: Lang, toLang: Lang): string {
  if (fromLang === toLang || !path || path === '/') return path;

  const parts = path.split('/').filter(Boolean);
  if (parts.length === 0) return path;

  // 404 page: always fall back to target-lang home
  if (parts[0] === '404') return '/';

  // Blog post: /blog/<slug>
  if (parts[0] === 'blog' && parts[1]) {
    const mapped = findPair(blogPairs, parts[1]);
    if (mapped && mapped[toLang]) return `/blog/${mapped[toLang]}/`;
    return '/blog/';
  }

  // Insights article: fall back to insights index if no translation exists
  if (parts[0] === 'insights' && parts[1]) {
    const mapped = findPair(insightPairs, parts[1]);
    return mapped?.[toLang] ? `/insights/${mapped[toLang]}/` : '/insights/';
  }

  const translated = parts.map(seg => {
    const map = pathSegments[seg];
    return map ? map[toLang] : seg;
  });

  const trailingSlash = path.endsWith('/') ? '/' : '';
  return '/' + translated.join('/') + trailingSlash;
}
