import type { APIRoute } from 'astro';
import { getCollection } from 'astro:content';

const BASE_URL = 'https://beyondpanamarelocation.com';

interface SitemapEntry {
  loc: string;
  lastmod: string;
  changefreq: string;
  priority: string;
}

function buildXml(entries: SitemapEntry[]): string {
  const urls = entries
    .map(
      ({ loc, lastmod, changefreq, priority }) => `  <url>
    <loc>${BASE_URL}${loc}</loc>
    <lastmod>${lastmod}</lastmod>
    <changefreq>${changefreq}</changefreq>
    <priority>${priority}</priority>
  </url>`
    )
    .join('\n');

  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${urls}
</urlset>`;
}

export const GET: APIRoute = async () => {
  const today = new Date().toISOString().split('T')[0];

  const staticPages: SitemapEntry[] = [
    // Homepages
    { loc: '/en',      changefreq: 'daily',   priority: '1.0', lastmod: today },
    { loc: '/es',      changefreq: 'daily',   priority: '1.0', lastmod: today },
    { loc: '/fr',      changefreq: 'monthly', priority: '0.7', lastmod: today },

    // Quiz
    { loc: '/en/quiz', changefreq: 'monthly', priority: '0.9', lastmod: today },
    { loc: '/es/quiz', changefreq: 'monthly', priority: '0.9', lastmod: today },
    { loc: '/fr/quiz', changefreq: 'monthly', priority: '0.7', lastmod: today },

    // Areas / Zonas index
    { loc: '/en/areas',                          changefreq: 'weekly',  priority: '0.8', lastmod: today },
    { loc: '/en/areas/panama-city',              changefreq: 'weekly',  priority: '0.7', lastmod: today },
    { loc: '/en/areas/boquete',                  changefreq: 'weekly',  priority: '0.7', lastmod: today },
    { loc: '/en/areas/coronado',                 changefreq: 'weekly',  priority: '0.7', lastmod: today },
    { loc: '/en/areas/bocas-del-toro',           changefreq: 'weekly',  priority: '0.7', lastmod: today },
    { loc: '/en/areas/san-carlos',               changefreq: 'monthly', priority: '0.6', lastmod: today },
    { loc: '/en/areas/pedasi',                   changefreq: 'monthly', priority: '0.6', lastmod: today },

    { loc: '/es/zonas',                          changefreq: 'weekly',  priority: '0.8', lastmod: today },
    { loc: '/es/zonas/ciudad-de-panama',         changefreq: 'weekly',  priority: '0.7', lastmod: today },
    { loc: '/es/zonas/boquete',                  changefreq: 'weekly',  priority: '0.7', lastmod: today },
    { loc: '/es/zonas/coronado',                 changefreq: 'weekly',  priority: '0.7', lastmod: today },
    { loc: '/es/zonas/bocas-del-toro',           changefreq: 'weekly',  priority: '0.7', lastmod: today },
    { loc: '/es/zonas/san-carlos',               changefreq: 'monthly', priority: '0.6', lastmod: today },
    { loc: '/es/zonas/pedasi',                   changefreq: 'monthly', priority: '0.6', lastmod: today },

    // Visas — EN
    { loc: '/en/visas',                          changefreq: 'weekly',  priority: '0.8', lastmod: today },
    { loc: '/en/visas/pensionado',               changefreq: 'monthly', priority: '0.7', lastmod: today },
    { loc: '/en/visas/friendly-nations',         changefreq: 'monthly', priority: '0.7', lastmod: today },
    { loc: '/en/visas/digital-nomad',            changefreq: 'monthly', priority: '0.7', lastmod: today },
    { loc: '/en/visas/qualified-investor',       changefreq: 'monthly', priority: '0.7', lastmod: today },
    { loc: '/en/visas/reforestation',            changefreq: 'monthly', priority: '0.6', lastmod: today },
    { loc: '/en/visas/rentista-retirado',        changefreq: 'monthly', priority: '0.6', lastmod: today },
    { loc: '/en/visas/comparison',               changefreq: 'monthly', priority: '0.7', lastmod: today },

    // Visas — ES
    { loc: '/es/visas',                          changefreq: 'weekly',  priority: '0.8', lastmod: today },
    { loc: '/es/visas/pensionado',               changefreq: 'monthly', priority: '0.7', lastmod: today },
    { loc: '/es/visas/naciones-amigas',          changefreq: 'monthly', priority: '0.7', lastmod: today },
    { loc: '/es/visas/nomada-digital',           changefreq: 'monthly', priority: '0.7', lastmod: today },
    { loc: '/es/visas/inversionista-calificado', changefreq: 'monthly', priority: '0.7', lastmod: today },
    { loc: '/es/visas/reforestacion',            changefreq: 'monthly', priority: '0.6', lastmod: today },
    { loc: '/es/visas/rentista-retirado',        changefreq: 'monthly', priority: '0.6', lastmod: today },
    { loc: '/es/visas/comparativa',              changefreq: 'monthly', priority: '0.7', lastmod: today },

    // Visas — FR
    { loc: '/fr/visas',                          changefreq: 'monthly', priority: '0.7', lastmod: today },
    { loc: '/fr/visas/pensionado',               changefreq: 'monthly', priority: '0.6', lastmod: today },
    { loc: '/fr/visas/nations-amies',            changefreq: 'monthly', priority: '0.6', lastmod: today },
    { loc: '/fr/visas/nomade-digital',           changefreq: 'monthly', priority: '0.6', lastmod: today },
    { loc: '/fr/visas/investisseur-qualifie',    changefreq: 'monthly', priority: '0.6', lastmod: today },
    { loc: '/fr/visas/reboisement',              changefreq: 'monthly', priority: '0.5', lastmod: today },
    { loc: '/fr/visas/rentista-retirado',        changefreq: 'monthly', priority: '0.5', lastmod: today },

    // Blog index
    { loc: '/en/blog', changefreq: 'weekly', priority: '0.7', lastmod: today },
    { loc: '/es/blog', changefreq: 'weekly', priority: '0.7', lastmod: today },

    // Cost of living / calculadora
    { loc: '/en/cost-of-living', changefreq: 'monthly', priority: '0.8', lastmod: today },
    { loc: '/es/costo-de-vida',  changefreq: 'monthly', priority: '0.8', lastmod: today },

    // About / Sobre nosotros
    { loc: '/en/about',          changefreq: 'yearly', priority: '0.5', lastmod: today },
    { loc: '/es/sobre-nosotros', changefreq: 'yearly', priority: '0.5', lastmod: today },

    // Contact
    { loc: '/en/contact', changefreq: 'yearly', priority: '0.5', lastmod: today },
    { loc: '/es/contacto', changefreq: 'yearly', priority: '0.5', lastmod: today },
  ];

  // Dynamic blog post entries
  const allPosts = await getCollection('blog', ({ data }) => !data.draft);

  const blogEntries: SitemapEntry[] = allPosts.map((post) => {
    const langPrefix = post.data.lang === 'es' ? 'es' : 'en';
    const slug = post.id.replace(`${langPrefix}/`, '');
    const lastmod = post.data.pubDate.toISOString().split('T')[0];
    return {
      loc: `/${langPrefix}/blog/${slug}`,
      changefreq: 'monthly',
      priority: '0.6',
      lastmod,
    };
  });

  const allEntries = [...staticPages, ...blogEntries];

  return new Response(buildXml(allEntries), {
    headers: {
      'Content-Type': 'application/xml; charset=utf-8',
    },
  });
};
