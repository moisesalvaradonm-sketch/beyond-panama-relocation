import { getCollection } from 'astro:content';

export const SITE_URL = 'https://beyondpanamarelocation.com';

import { NOINDEX_PATHS } from './noindex-paths.mjs';
export { NOINDEX_PATHS };

export function normalizePath(p: string): string {
  return p.endsWith('/') ? p : `${p}/`;
}

export function isNoindexPath(p: string): boolean {
  return NOINDEX_PATHS.has(normalizePath(p));
}

let routesPromise: Promise<Set<string>> | null = null;

/** Every real static route (pages + blog + insights), with trailing slash. */
export function getRouteSet(): Promise<Set<string>> {
  routesPromise ??= (async () => {
    const routes = new Set<string>();
    const files = import.meta.glob('/src/pages/**/*.astro');
    for (const f of Object.keys(files)) {
      if (f.includes('[')) continue;
      let r = f.replace('/src/pages', '').replace(/\.astro$/, '').replace(/\/index$/, '');
      routes.add(`${r}/`);
    }
    for (const p of await getCollection('blog', ({ data }) => !data.draft)) routes.add(`/${p.id.split('/')[0]}/blog/${p.id.split('/').slice(1).join('/')}/`);
    for (const p of await getCollection('insights', ({ data }) => !data.draft)) routes.add(`/${p.id.split('/')[0]}/insights/${p.id.split('/').slice(1).join('/')}/`);
    return routes;
  })();
  return routesPromise;
}
