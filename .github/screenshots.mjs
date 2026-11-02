// Erstellt Screenshots der wichtigsten Seiten (Desktop und Mobil, hell und dunkel) für die visuelle Prüfung.
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const base = process.env.BASE_URL || 'http://127.0.0.1:8000';
const out = process.env.OUT_DIR || 'shots';
mkdirSync(out, { recursive: true });

const pages = [
  ['dashboard', '/'],
  ['contracts', '/contracts'],
  ['pick-template', '/contracts/create'],
  ['form-kvg', '/contracts/create?template=kvg-grundversicherung'],
  ['contract-show', '/contracts/1'],
  ['letter', '/contracts/1/letter'],
  ['expiry', '/expiry'],
  ['savings', '/savings'],
  ['settings', '/settings'],
];

const variants = [
  { name: 'desktop', viewport: { width: 1360, height: 900 }, colorScheme: 'light' },
  { name: 'mobile', viewport: { width: 390, height: 844 }, colorScheme: 'light', isMobile: true, deviceScaleFactor: 2 },
  { name: 'dark', viewport: { width: 1360, height: 900 }, colorScheme: 'dark' },
];

const browser = await chromium.launch();
for (const v of variants) {
  const ctx = await browser.newContext({ viewport: v.viewport, colorScheme: v.colorScheme, isMobile: v.isMobile, deviceScaleFactor: v.deviceScaleFactor || 1 });
  const page = await ctx.newPage();

  await page.goto(base + '/login');
  if (v.name === 'desktop') await page.screenshot({ path: `${out}/${v.name}-login.png` });
  await page.fill('#email', 'demo@example.ch');
  await page.fill('#password', 'demo-passwort-123');
  await Promise.all([page.waitForNavigation(), page.click('button:has-text("Anmelden")')]);

  const list = v.name === 'dark' ? pages.filter(([n]) => ['dashboard', 'contract-show', 'form-kvg'].includes(n)) : pages;
  for (const [name, path] of list) {
    await page.goto(base + path, { waitUntil: 'networkidle' });
    await page.waitForTimeout(600);
    await page.screenshot({ path: `${out}/${v.name}-${name}.png`, fullPage: true });
  }
  await ctx.close();
}
await browser.close();
