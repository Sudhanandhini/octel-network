// Generates abstract, on-brand SVG placeholder images for the Exrox clone.
// Run: node scripts/gen-placeholders.js
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..', 'assets', 'img');

const NAVY = '#00252C';
const NAVY2 = '#043138';
const MINT = '#B9F8B1';
const GREEN = '#91D089';
const ASH = '#F5F5F5';
const GRAY = '#99A8AB';

function write(rel, svg) {
  const full = path.join(ROOT, rel);
  fs.mkdirSync(path.dirname(full), { recursive: true });
  fs.writeFileSync(full, svg.trim());
  console.log('wrote', rel);
}

// Deterministic pseudo-random from seed
function rnd(seed) {
  let s = seed;
  return () => {
    s = (s * 9301 + 49297) % 233280;
    return s / 233280;
  };
}

// Abstract photographic-style placeholder: gradient + soft blobs + faint grid.
function scenePlaceholder({ w, h, seed = 1, tone = 'navy', label = '' }) {
  const r = rnd(seed);
  const tones = {
    navy: [NAVY, NAVY2],
    mint: [GREEN, MINT],
    ash: [ASH, '#e9e9e9'],
    dual: [NAVY, GREEN],
  };
  const [c1, c2] = tones[tone] || tones.navy;
  const gid = `g${seed}`;
  const bid = `b${seed}`;
  let blobs = '';
  for (let i = 0; i < 4; i++) {
    const bx = r() * w;
    const by = r() * h;
    const br = (0.18 + r() * 0.22) * Math.max(w, h);
    const op = 0.10 + r() * 0.10;
    blobs += `<circle cx="${bx.toFixed(0)}" cy="${by.toFixed(0)}" r="${br.toFixed(0)}" fill="url(#${bid})" opacity="${op.toFixed(2)}" />`;
  }
  const lines = [];
  const step = Math.max(w, h) / 10;
  for (let i = -h; i < w + h; i += step) {
    lines.push(`<line x1="${i}" y1="0" x2="${i - h}" y2="${h}" stroke="#ffffff" stroke-opacity="0.045" stroke-width="1"/>`);
  }
  return `
<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">
  <defs>
    <linearGradient id="${gid}" x1="0" y1="0" x2="${w}" y2="${h}" gradientUnits="userSpaceOnUse">
      <stop offset="0" stop-color="${c1}"/>
      <stop offset="1" stop-color="${c2}"/>
    </linearGradient>
    <radialGradient id="${bid}" cx="50%" cy="50%" r="50%">
      <stop offset="0" stop-color="#ffffff"/>
      <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="${w}" height="${h}" fill="url(#${gid})"/>
  <g>${lines.join('')}</g>
  ${blobs}
  ${label ? `<text x="24" y="${h - 24}" font-family="DM Sans, Arial, sans-serif" font-size="${Math.max(12, w * 0.018)}" fill="#ffffff" fill-opacity="0.55">${label}</text>` : ''}
</svg>`;
}

function portraitPlaceholder({ w, h, seed = 1, initials = 'EX', tone = 'navy' }) {
  const r = rnd(seed);
  const tones = { navy: [NAVY, NAVY2], mint: [GREEN, MINT], dual: [NAVY, GREEN] };
  const [c1, c2] = tones[tone] || tones.navy;
  const gid = `p${seed}`;
  const headR = w * 0.16;
  const headCy = h * 0.38;
  const shoulderY = h * 0.62;
  return `
<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">
  <defs>
    <linearGradient id="${gid}" x1="0" y1="0" x2="${w}" y2="${h}" gradientUnits="userSpaceOnUse">
      <stop offset="0" stop-color="${c1}"/>
      <stop offset="1" stop-color="${c2}"/>
    </linearGradient>
  </defs>
  <rect width="${w}" height="${h}" fill="url(#${gid})"/>
  <circle cx="${w / 2}" cy="${headCy}" r="${headR}" fill="#ffffff" fill-opacity="0.18"/>
  <path d="M ${w * 0.18} ${h} C ${w * 0.18} ${shoulderY}, ${w * 0.82} ${shoulderY}, ${w * 0.82} ${h} Z" fill="#ffffff" fill-opacity="0.14"/>
  <text x="50%" y="${headCy + headR * 0.35}" text-anchor="middle" font-family="Rethink Sans, DM Sans, Arial, sans-serif" font-weight="700" font-size="${headR}" fill="#ffffff" fill-opacity="0.85">${initials}</text>
</svg>`;
}

function avatarPlaceholder({ size = 80, seed = 1, initials = 'C' }) {
  const r = rnd(seed);
  const hue = Math.floor(r() * 360);
  const c1 = `hsl(${hue},45%,28%)`;
  const c2 = `hsl(${(hue + 40) % 360},55%,45%)`;
  const gid = `a${seed}`;
  return `
<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
  <defs>
    <linearGradient id="${gid}" x1="0" y1="0" x2="${size}" y2="${size}" gradientUnits="userSpaceOnUse">
      <stop offset="0" stop-color="${c1}"/>
      <stop offset="1" stop-color="${c2}"/>
    </linearGradient>
  </defs>
  <rect width="${size}" height="${size}" fill="url(#${gid})"/>
  <text x="50%" y="54%" text-anchor="middle" dominant-baseline="middle" font-family="Rethink Sans, Arial, sans-serif" font-weight="700" font-size="${size * 0.38}" fill="#ffffff">${initials}</text>
</svg>`;
}

function logoWordmark({ w = 160, h = 44, text = 'EXROX', color = '#ffffff' }) {
  return `
<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">
  <rect x="0" y="${h * 0.28}" width="${h * 0.44}" height="${h * 0.44}" rx="${h * 0.12}" fill="${GREEN}"/>
  <text x="${h * 0.62}" y="${h * 0.72}" font-family="Rethink Sans, Arial, sans-serif" font-weight="800" font-size="${h * 0.52}" fill="${color}" letter-spacing="0.5">${text}</text>
</svg>`;
}

function brandLogo({ w = 140, h = 48, seed = 1, name = 'BRAND' }) {
  const r = rnd(seed);
  const shapes = ['circle', 'rect', 'triangle'];
  const shape = shapes[Math.floor(r() * shapes.length)];
  let mark = '';
  const s = h * 0.5;
  if (shape === 'circle') mark = `<circle cx="${s / 2 + 2}" cy="${h / 2}" r="${s / 2}" fill="${GRAY}"/>`;
  else if (shape === 'rect') mark = `<rect x="2" y="${h / 2 - s / 2}" width="${s}" height="${s}" rx="4" fill="${GRAY}"/>`;
  else mark = `<polygon points="${s / 2 + 2},${h / 2 - s / 2} ${2 + s},${h / 2 + s / 2} 2,${h / 2 + s / 2}" fill="${GRAY}"/>`;
  return `
<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">
  ${mark}
  <text x="${s + 12}" y="${h / 2 + h * 0.07}" dominant-baseline="middle" font-family="DM Sans, Arial, sans-serif" font-weight="700" font-size="${h * 0.34}" fill="${GRAY}" letter-spacing="1">${name}</text>
</svg>`;
}

// ---- Generate ----

// Hero slides (16:9 large)
[1, 2, 3].forEach((n) => write(`hero/hero-slide-${n}.svg`, scenePlaceholder({ w: 1920, h: 1080, seed: n, tone: 'navy', label: `EXROX — HERO ${n}` })));

// About
write('about/about-img-1.svg', scenePlaceholder({ w: 700, h: 820, seed: 11, tone: 'mint', label: 'ABOUT' }));
write('about/about-img-10.svg', scenePlaceholder({ w: 700, h: 820, seed: 12, tone: 'mint', label: 'ABOUT US' }));
write('about/wh-img-1.svg', scenePlaceholder({ w: 480, h: 360, seed: 13, tone: 'ash', label: '' }));
write('about/wh-img-2.svg', scenePlaceholder({ w: 480, h: 360, seed: 14, tone: 'ash', label: '' }));
write('about/ceo.svg', portraitPlaceholder({ w: 200, h: 200, seed: 15, initials: 'CW', tone: 'dual' }));

// Services
write('services/service-bg-1.svg', scenePlaceholder({ w: 1920, h: 1200, seed: 21, tone: 'navy', label: 'EXROX SERVICES' }));

// Case studies (4)
[1, 2, 3, 4].forEach((n) => write(`case-study/case-${n}.svg`, scenePlaceholder({ w: 760, h: 620, seed: 30 + n, tone: n % 2 ? 'dual' : 'mint', label: `CASE STUDY 0${n}` })));

// Clients / testimonial avatars (8)
const names = ['RC', 'SS', 'BC', 'RP', 'JM', 'AK', 'TL', 'NV'];
for (let n = 1; n <= 8; n++) write(`clients/client-${n}.svg`, avatarPlaceholder({ size: 160, seed: 40 + n, initials: names[n - 1] }));

// Blog (5)
[1, 2, 3, 4, 5].forEach((n) => write(`blog/blog-${n}.svg`, scenePlaceholder({ w: 700, h: 500, seed: 50 + n, tone: n % 2 ? 'navy' : 'mint', label: `ARTICLE 0${n}` })));

// Brand logos (10)
const brandNames = ['NORTHPEAK', 'VERIDIAN', 'ATLASCO', 'BLUEHARBOR', 'STONEBRIDGE', 'CIVIC & CO', 'MERIDIAN', 'OAKFIELD', 'CASCADE', 'SUMMIT+'];
for (let n = 1; n <= 10; n++) write(`brand/brand-logo-${n}.svg`, brandLogo({ seed: 60 + n, name: brandNames[n - 1] }));

// Breadcrumb banners (3 variants used across inner pages)
[1, 2, 3].forEach((n) => write(`breadcrumb/br-${n}.svg`, scenePlaceholder({ w: 1920, h: 460, seed: 70 + n, tone: 'navy', label: '' })));

// Promo full-bleed bg
write('promo-bg-1.svg', scenePlaceholder({ w: 1920, h: 900, seed: 80, tone: 'dual', label: '' }));

// Team / leaders (8 portraits)
const initials = ['JG', 'MP', 'LR', 'DK', 'HS', 'TW', 'CN', 'OB'];
for (let n = 1; n <= 8; n++) write(`team/team-${n}.svg`, portraitPlaceholder({ w: 560, h: 680, seed: 90 + n, initials: initials[n - 1], tone: n % 2 ? 'navy' : 'dual' }));

// Employer logos (3, small circular)
for (let n = 1; n <= 3; n++) write(`career/employer-logo-${n}.svg`, brandLogo({ w: 100, h: 40, seed: 100 + n, name: 'CO' }));

// Site logos
write('logo-white.svg', logoWordmark({ text: 'EXROX', color: '#ffffff' }));
write('logo-dark.svg', logoWordmark({ text: 'EXROX', color: NAVY }));

console.log('Done.');
