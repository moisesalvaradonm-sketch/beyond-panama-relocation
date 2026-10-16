import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const blog = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/blog' }),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    pubDate: z.date(),
    updatedDate: z.date().optional(),
    category: z.enum(['zonas', 'visas', 'costo-de-vida', 'proceso', 'lifestyle']),
    readTime: z.number(),
    lang: z.enum(['es', 'en', 'fr']),
    draft: z.boolean().default(false),
    image: z.string().optional(),
    imageAlt: z.string().optional(),
  }),
});

const insights = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/insights' }),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    pubDate: z.date(),
    updatedDate: z.date().optional(),
    category: z.enum(['real-estate', 'legal', 'finance', 'lifestyle', 'client-story', 'moving-guide']),
    readTime: z.number(),
    lang: z.enum(['es', 'en', 'fr']),
    draft: z.boolean().default(false),
    image: z.string().optional(),
    imageAlt: z.string().optional(),
    authorName: z.string(),
    authorRole: z.string(),
    authorBio: z.string().optional(),
    authorAvatar: z.string().optional(),
    authorWebsite: z.string().optional(),
    authorPhone: z.string().optional(),
  }),
});

export const collections = { blog, insights };
