// @ts-check
import { defineConfig } from 'astro/config';
import { readFileSync, existsSync } from 'node:fs';
import tailwindcss from '@tailwindcss/vite';
import react from '@astrojs/react';
import sitemap from '@astrojs/sitemap';
import { NOINDEX_PATHS } from './src/noindex-paths.mjs';

const SITE = 'https://beyondpanamarelocation.com';

/** Real last-modified date of the source behind a URL (blog/insights only: frontmatter updatedDate > pubDate). */
function lastmodFor(pathname) {
  const parts = pathname.split('/').filter(Boolean); // ['en','blog','slug']
  const candidates = [];
  if (parts[1] === 'blog' || parts[1] === 'insights') {
    if (parts[2]) candidates.push(`src/content/${parts[1]}/${parts[0]}/${parts[2]}.md`);
  } else {
    const rel = parts.join('/');
    candidates.push(`src/pages/${rel || 'index'}.astro`, `src/pages/${rel}/index.astro`);
  }
  for (const f of candidates) {
    if (!existsSync(f)) continue;
    if (f.endsWith('.md')) {
      const fm = readFileSync(f, 'utf8').match(/^---\n([\s\S]*?)\n---/)?.[1] ?? '';
      const d = fm.match(/^updatedDate:\s*(\d{4}-\d{2}-\d{2})/m)?.[1] ?? fm.match(/^pubDate:\s*(\d{4}-\d{2}-\d{2})/m)?.[1];
      if (d) return new Date(d).toISOString();
    }
    return undefined; // plain pages carry no reliable edit date: omit rather than fake it
  }
  return undefined;
}

export default defineConfig({
  site: SITE,
  trailingSlash: 'always',
  vite: {
    plugins: [tailwindcss()],
  },
  integrations: [
    react(),
    sitemap({
      // Only canonical, indexable URLs. hreflang lives in the page <head>.
      filter: (page) => {
        const path = new URL(page).pathname;
        return path !== '/' && !path.startsWith('/admin') && !path.startsWith('/404') && !NOINDEX_PATHS.has(path);
      },
      serialize: (item) => {
        const lastmod = lastmodFor(new URL(item.url).pathname);
        return lastmod ? { ...item, lastmod } : item;
      },
    }),
  ],
  i18n: {
    defaultLocale: 'es',
    locales: ['es', 'en'],
    routing: {
      prefixDefaultLocale: true,
    },
  },
});
