// Assembles every page from partials/render.js + content/<slug>.html fragments.
// Run: node scripts/build.js
const fs = require('fs');
const path = require('path');
const { renderHeader, renderFooter, renderBreadcrumb } = require('../partials/render.js');

const ROOT = path.join(__dirname, '..');
const CONTENT_DIR = path.join(ROOT, 'content');

const pages = [
  {
    slug: 'index', file: 'home.html', bodyId: 'home',
    title: 'Exrox — Accounting & Management Consulting',
    description: 'Exrox is a full-service accounting and management consulting firm helping businesses grow with clarity, confidence and control.',
    variant: 'transparent', active: 'home', breadcrumb: null,
  },
  {
    slug: 'about', file: 'about.html', bodyId: 'about',
    title: 'About Us — Exrox',
    description: 'Learn about Exrox — our story, our leadership, and the mission behind our accounting and consulting practice.',
    variant: 'inner', active: 'about', breadcrumb: { title: 'About Us', img: 'br-1.svg' },
  },
  {
    slug: 'services', file: 'services.html', bodyId: 'services',
    title: 'Our Services — Exrox',
    description: 'Explore the full range of accounting, tax, payroll, audit and advisory services Exrox offers.',
    variant: 'inner', active: 'services', breadcrumb: { title: 'Our Services', img: 'br-2.svg' },
  },
  {
    slug: 'service-details', file: 'service-details.html', bodyId: 'service-details',
    title: 'Service Details — Exrox',
    description: 'A closer look at how Exrox delivers tax planning and compliance services for growing businesses.',
    variant: 'inner', active: 'services', breadcrumb: { title: 'Service Details', img: 'br-2.svg' },
  },
  {
    slug: 'industries', file: 'industries.html', bodyId: 'industries',
    title: 'Industries We Serve — Exrox',
    description: 'Exrox brings industry-specific financial expertise to healthcare, manufacturing, technology, retail and real estate.',
    variant: 'inner', active: 'industries', breadcrumb: { title: 'Industries', img: 'br-3.svg' },
  },
  {
    slug: 'case-studies', file: 'case-studies.html', bodyId: 'case-studies',
    title: 'Case Studies — Exrox',
    description: 'Real client outcomes: how Exrox has helped businesses cut costs, raise capital, and scale with confidence.',
    variant: 'inner', active: 'case', breadcrumb: { title: 'Case Studies', img: 'br-1.svg' },
  },
  {
    slug: 'case-study-details', file: 'case-study-details.html', bodyId: 'case-study-details',
    title: 'Case Study Details — Exrox',
    description: 'A detailed look at how Exrox helped FastTrack Apparel streamline its finances and boost profitability.',
    variant: 'inner', active: 'case', breadcrumb: { title: 'Case Study Details', img: 'br-1.svg' },
  },
  {
    slug: 'team', file: 'team.html', bodyId: 'team',
    title: 'Our Leadership Team — Exrox',
    description: 'Meet the certified advisors, CPAs and consultants behind Exrox.',
    variant: 'inner', active: 'team', breadcrumb: { title: 'Our Team', img: 'br-2.svg' },
  },
  {
    slug: 'pricing', file: 'pricing.html', bodyId: 'pricing',
    title: 'Pricing Plans — Exrox',
    description: 'Transparent, flexible pricing plans for businesses of every size.',
    variant: 'inner', active: 'pricing', breadcrumb: { title: 'Pricing Plan', img: 'br-3.svg' },
  },
  {
    slug: 'faq', file: 'faq.html', bodyId: 'faq',
    title: 'Frequently Asked Questions — Exrox',
    description: 'Answers to common questions about working with Exrox.',
    variant: 'inner', active: 'faq', breadcrumb: { title: 'FAQ', img: 'br-1.svg' },
  },
  {
    slug: 'blog', file: 'blog.html', bodyId: 'blog',
    title: 'Blog — Exrox',
    description: 'Financial strategies, industry trends, and expert advice from the Exrox team.',
    variant: 'inner', active: 'blog', breadcrumb: { title: 'Blog', img: 'br-2.svg' },
  },
  {
    slug: 'blog-details', file: 'blog-details.html', bodyId: 'blog-details',
    title: '7 Common Accounting Mistakes Small Businesses Make — Exrox',
    description: 'A detailed article on the most common accounting mistakes small businesses make and how to avoid them.',
    variant: 'inner', active: 'blog', breadcrumb: { title: 'Blog Details', img: 'br-2.svg' },
  },
  {
    slug: 'contact', file: 'contact.html', bodyId: 'contact',
    title: 'Contact Us — Exrox',
    description: 'Get in touch with Exrox for a free consultation about your accounting and advisory needs.',
    variant: 'inner', active: 'contact', breadcrumb: { title: 'Contact Us', img: 'br-3.svg' },
  },
  {
    slug: 'terms', file: 'terms.html', bodyId: 'terms',
    title: 'Terms & Conditions — Exrox',
    description: 'Terms and conditions for using the Exrox website and services.',
    variant: 'inner', active: '', breadcrumb: { title: 'Terms & Conditions', img: 'br-1.svg' },
  },
  {
    slug: 'privacy', file: 'privacy.html', bodyId: 'privacy',
    title: 'Privacy Policy — Exrox',
    description: 'How Exrox collects, uses and protects your information.',
    variant: 'inner', active: '', breadcrumb: { title: 'Privacy Policy', img: 'br-1.svg' },
  },
];

const headTemplate = fs.readFileSync(path.join(ROOT, 'partials', 'head.html'), 'utf8');

function renderHead(title, description, bodyId) {
  return headTemplate
    .replace('{{TITLE}}', title)
    .replace('{{DESCRIPTION}}', description)
    .replace('{{BODY_ID}}', bodyId);
}

let built = 0;
for (const p of pages) {
  const contentPath = path.join(CONTENT_DIR, p.file);
  if (!fs.existsSync(contentPath)) {
    console.warn('SKIP (missing content):', p.file);
    continue;
  }
  const body = fs.readFileSync(contentPath, 'utf8');
  const html = [
    renderHead(p.title, p.description, p.bodyId),
    renderHeader({ variant: p.variant, active: p.active }),
    '<main id="main">',
    p.breadcrumb ? renderBreadcrumb(p.breadcrumb) : '',
    body,
    '</main>',
    renderFooter(),
  ].join('\n');

  fs.writeFileSync(path.join(ROOT, `${p.slug}.html`), html);
  built++;
  console.log('built', `${p.slug}.html`);
}
console.log(`Done. ${built}/${pages.length} pages built.`);
