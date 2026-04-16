import {PageRow} from "@/pages/Blog/Posts/types.ts";

const TITLES = [
  'Home', 'About', 'Contact', 'Pricing', 'Blog', 'Changelog',
  'Privacy Policy', 'Terms of Service', 'FAQ', 'Careers',
  'Documentation', 'Getting Started', 'Tutorials', 'API Reference',
  'Support', 'Roadmap', 'Press', 'Partners', 'Integrations', 'Security',
  'Downloads', 'Newsletter', 'Community', 'Events', 'Case Studies',
  'Testimonials', 'Team', 'Investors', 'Contact Sales', 'Demo',
];

const AUTHORS = ['Alice', 'Bob', 'Carol', 'Dave'];

export const MOCK_PAGES: PageRow[] = TITLES.map((title, i) => ({
  id: i + 1,
  title,
  slug: title.toLowerCase().replace(/\s+/g, '-'),
  status: i % 3 === 0 ? 'draft' : 'published',
  author: AUTHORS[i % AUTHORS.length]!,
  updatedAt: new Date(Date.now() - i * 1000 * 60 * 60 * 24).toISOString().slice(0, 10),
}));
