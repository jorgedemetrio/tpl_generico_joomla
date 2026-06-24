// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Fase 3 (SEO) — metadados de <head> (canonical, Open Graph, Twitter Cards,
 * theme-color) e JSON-LD global (Organization + WebSite), além do contrato de
 * landmark único "banner" (achado G1).
 *
 * Regra de negócio (não só "carrega"):
 *   - canonical absoluto e sem query;
 *   - og:url == canonical; og:type válido; og:image presente => twitter:card
 *     "summary_large_image";
 *   - Organization e WebSite são JSON-LD válidos; WebSite expõe SearchAction;
 *   - existe EXATAMENTE um role="banner" na página (o <header>); a <section
 *     id="banner"> não duplica o landmark.
 */

const FIXTURE = '/tests/fixtures/seo-head.html';

/** Valor de uma meta tag por name ou property. */
async function metaContent(page, selector) {
  const loc = page.locator(selector);
  if ((await loc.count()) === 0) return null;
  return loc.first().getAttribute('content');
}

test('SEO head: sem erro/warning de console e sem chave de tradução crua', async ({ page }) => {
  const problems = [];
  page.on('console', (msg) => {
    const t = msg.type();
    if (t === 'error' || t === 'warning') problems.push(`[${t}] ${msg.text()}`);
  });
  page.on('pageerror', (err) => problems.push(`[pageerror] ${err.message}`));

  await page.goto(FIXTURE);
  await expect(page.locator('main#main-content')).toBeVisible();

  const html = await page.content();
  expect(html).not.toMatch(/TPL_GENERICO_[A-Z0-9_]+/);

  expect(problems, problems.join('\n')).toEqual([]);
});

test('SEO head: canonical absoluto e sem query', async ({ page }) => {
  await page.goto(FIXTURE);
  const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');
  expect(canonical, 'canonical ausente').toBeTruthy();
  expect(canonical).toMatch(/^https?:\/\//);
  expect(canonical).not.toContain('?');
});

test('SEO head: Open Graph completo e coerente', async ({ page }) => {
  await page.goto(FIXTURE);

  const siteName = await metaContent(page, 'meta[property="og:site_name"]');
  const ogTitle = await metaContent(page, 'meta[property="og:title"]');
  const ogType = await metaContent(page, 'meta[property="og:type"]');
  const ogUrl = await metaContent(page, 'meta[property="og:url"]');
  const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');

  expect(siteName).toBeTruthy();
  expect(ogTitle).toBeTruthy();
  expect(['website', 'article']).toContain(ogType);
  // og:url deve casar com a URL canônica.
  expect(ogUrl).toBe(canonical);
});

test('SEO head: Twitter Card coerente com a presença de imagem', async ({ page }) => {
  await page.goto(FIXTURE);
  const ogImage = await metaContent(page, 'meta[property="og:image"]');
  const twCard = await metaContent(page, 'meta[name="twitter:card"]');
  expect(twCard).toBeTruthy();
  // Com imagem, o card é grande; o título do Twitter deve existir.
  if (ogImage) {
    expect(twCard).toBe('summary_large_image');
  }
  expect(await metaContent(page, 'meta[name="twitter:title"]')).toBeTruthy();
});

test('SEO head: theme-color é uma cor hex', async ({ page }) => {
  await page.goto(FIXTURE);
  const themeColor = await metaContent(page, 'meta[name="theme-color"]');
  expect(themeColor).toMatch(/^#[0-9a-fA-F]{3,6}$/);
});

test('SEO head: JSON-LD Organization + WebSite válidos (com SearchAction)', async ({ page }) => {
  await page.goto(FIXTURE);

  const blocks = await page.locator('script[type="application/ld+json"]').allTextContents();
  expect(blocks.length).toBeGreaterThanOrEqual(2);

  const parsed = blocks.map((b) => JSON.parse(b)); // lança se algum for inválido
  const byType = Object.fromEntries(parsed.map((p) => [p['@type'], p]));

  expect(byType.Organization).toBeTruthy();
  expect(byType.Organization.name).toBeTruthy();
  expect(byType.Organization.url).toMatch(/^https?:\/\//);

  const website = byType.WebSite;
  expect(website).toBeTruthy();
  expect(website.potentialAction['@type']).toBe('SearchAction');
  expect(website.potentialAction.target).toContain('{search_term_string}');
});

test('SEO head: landmark "banner" é único (G1)', async ({ page }) => {
  await page.goto(FIXTURE);
  // Apenas o <header> carrega role="banner"; a <section id="banner"> usa aria-label.
  await expect(page.locator('[role="banner"]')).toHaveCount(1);
  await expect(page.locator('header[role="banner"]')).toHaveCount(1);
  await expect(page.locator('section#banner')).toHaveAttribute('aria-label', /.+/);
  await expect(page.locator('section#banner[role="banner"]')).toHaveCount(0);
});
