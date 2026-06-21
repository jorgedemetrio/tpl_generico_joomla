// @ts-check
const { test, expect } = require('@playwright/test');
const { attachConsole, expectNoConsoleProblems } = require('./_helpers');

/**
 * Fluxo funcional REAL do botão "voltar ao topo" contra o Joomla.
 *
 * Regra de negócio (template.js initBackToTop):
 *  - elegível só no DESKTOP (innerWidth >= 768) e em página LONGA (scrollHeight
 *    > 2x a altura visível);
 *  - aparece (.is-visible) ao rolar mais de 60% da altura visível;
 *  - ao clicar, rola de volta ao topo e some;
 *  - no MOBILE (< 768px) NUNCA aparece, por mais que role.
 */

const HOME = './';
const btn = '#backToTop';

// Emula "prefers-reduced-motion": o template rola de volta com behavior:'auto'
// (instantâneo) em vez de 'smooth' — testa o ramo de acessibilidade.
test.use({ reducedMotion: 'reduce' });

// O aviso de cookies (ligado, canto inferior) cobriria o botão e interceptaria
// o clique até o auto-aceite. Pré-consentir isola o teste do back-to-top.
async function preConsent(context) {
  await context.addCookies([
    { name: 'generico_cookie_consent', value: '1', domain: 'localhost', path: '/' },
  ]);
}

test('back-to-top: aparece ao rolar página longa (desktop) e volta ao topo', async ({ page, context }) => {
  await preConsent(context);
  await page.setViewportSize({ width: 1280, height: 600 });
  const c = attachConsole(page);
  await page.goto(HOME, { waitUntil: 'domcontentloaded' });

  const longEnough = await page.evaluate(
    () => document.documentElement.scrollHeight > window.innerHeight * 2
  );
  expect(longEnough, 'a home precisa ser longa o suficiente p/ o back-to-top').toBe(true);

  // No topo: oculto.
  await expect(page.locator(btn)).not.toHaveClass(/\bis-visible\b/);

  // Rola até o fim: aparece.
  await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
  await expect(page.locator(btn)).toHaveClass(/\bis-visible\b/);

  // Clica: volta ao topo e some.
  await page.locator(btn).click();
  await expect.poll(() => page.evaluate(() => window.scrollY), {
    message: 'deve voltar ao topo',
  }).toBeLessThan(50);
  await expect(page.locator(btn)).not.toHaveClass(/\bis-visible\b/);

  expectNoConsoleProblems(c, 'back-to-top');
});

test('back-to-top: NUNCA aparece no mobile (< 768px), mesmo rolando', async ({ page, context }) => {
  await preConsent(context);
  await page.setViewportSize({ width: 375, height: 700 });
  await page.goto(HOME, { waitUntil: 'domcontentloaded' });

  await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
  await page.waitForTimeout(300);
  await expect(page.locator(btn)).not.toHaveClass(/\bis-visible\b/);
});

test('back-to-top fica ACIMA do aviso de cookies e é clicável (não é coberto)', async ({ page, context }) => {
  // SEM pré-consentir: o aviso de cookies fica visível na base (era ele que
  // cobria o botão e interceptava o clique até o auto-aceite de 20s).
  await context.clearCookies();
  await page.setViewportSize({ width: 1280, height: 600 });
  const c = attachConsole(page);
  await page.goto(HOME, { waitUntil: 'domcontentloaded' });

  const cookie = page.locator('#cookieNotice');
  await expect(cookie, 'aviso de cookies visível').toBeVisible();

  await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
  const button = page.locator(btn);
  await expect(button).toHaveClass(/\bis-visible\b/);

  // Geometria: o botão fica inteiramente ACIMA do topo do aviso (não sobrepõe).
  const bb = await button.boundingBox();
  const cb = await cookie.boundingBox();
  expect(bb && cb, 'bounding boxes disponíveis').toBeTruthy();
  expect(bb.y + bb.height, 'base do back-to-top acima do topo do aviso').toBeLessThanOrEqual(cb.y + 1);

  // Clicável já (timeout curto): se estivesse coberto, o actionability falharia
  // em 3s em vez de esperar o aviso sumir sozinho (~20s).
  await button.click({ timeout: 3000 });
  await expect.poll(() => page.evaluate(() => window.scrollY), {
    message: 'volta ao topo',
  }).toBeLessThan(50);

  // O clique foi no botão, não no aviso: o consentimento NÃO foi dado.
  await expect(cookie).toBeVisible();

  expectNoConsoleProblems(c, 'back-to-top sobre cookie');
});
