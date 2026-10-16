/**
 * Pages kept out of the index (meta robots noindex,follow) AND out of the sitemap
 * AND out of hreflang clusters. Single source of truth: Layout.astro and
 * astro.config.mjs both read it.
 * Revisit FR entries once each page reaches ~600 words of unique content.
 */
export const NOINDEX_PATHS = new Set([
  // Conversion / low-intent pages
  '/en/quiz/', '/es/quiz/', '/fr/quiz/',
  '/en/testimonials/', '/es/testimonios/', '/fr/temoignages/',
  '/en/webinar/',
  '/es/propiedades/',
  // Insights hub: only one article per language so far
  '/en/insights/', '/es/insights/', '/fr/insights/',
  // Thin ES page (357 words) until expanded
  '/es/transporte/',
  // FR pages under ~500 words: translated stubs, not yet worth indexing
  '/fr/avantages-pensionado/', '/fr/banque-finances/', '/fr/communications/',
  '/fr/cout-de-la-vie/', '/fr/education-activites/', '/fr/geographie-climat/',
  '/fr/nouveautes-2025/', '/fr/prochaines-etapes/', '/fr/sante-assurances/',
  '/fr/transport/', '/fr/travailler-panama/',
  '/fr/zones/bocas-del-toro/', '/fr/zones/boquete/', '/fr/zones/chitre/',
  '/fr/zones/coronado/', '/fr/zones/david/', '/fr/zones/el-valle/',
  '/fr/zones/pedasi/', '/fr/zones/san-carlos/', '/fr/zones/santiago/',
  '/fr/zones/ville-de-panama/', '/fr/zones/volcan/',
]);

