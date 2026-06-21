// @ts-check
const { test, expect } = require('@playwright/test');
const { attachConsole, expectNoConsoleProblems } = require('./_helpers');

/**
 * Fluxo funcional REAL do aviso de cookies (template ligado por padrão,
 * cookieNotice=1) contra o Joomla.
 *
 * Regra de negócio (não basta "carregar"): aparece só para quem ainda não
 * consentiu; ao aceitar grava o cookie `generico_cookie_consent` (validade longa)
 * e NÃO reaparece nas próximas visitas — inclusive navegando para outra página.
 */

const HOME = './';
const OTHER = 'index.php?option=com_automoveis&view=lojas&Itemid=180';
const COOKIE = 'generico_cookie_consent';
const notice = '#cookieNotice';

async function hasConsent(page) {
  const cookies = await page.context().cookies();
  return cookies.some((c) => c.name === COOKIE && c.value === '1');
}

test('cookie: aparece sem consentimento, aceitar persiste e não reaparece ao navegar', async ({ page, context }) => {
  await context.clearCookies();
  const c = attachConsole(page);
  await page.goto(HOME, { waitUntil: 'load' });

  const el = page.locator(notice);
  await expect(el, 'o aviso deve aparecer no 1º acesso').toBeVisible();
  await expect(el).toHaveClass(/\bis-visible\b/);
  expect(await hasConsent(page), 'sem cookie antes de aceitar').toBe(false);

  await page.click('#cookieAccept');

  await expect.poll(() => hasConsent(page), { message: 'cookie de consentimento gravado' }).toBe(true);
  await expect(el).toBeHidden();

  // Nova página, mesma sessão: com o cookie presente o aviso não volta.
  await page.goto(OTHER, { waitUntil: 'load' });
  await page.waitForLoadState('networkidle').catch(() => {});
  await expect(page.locator(notice)).toBeHidden();

  expectNoConsoleProblems(c, 'cookie');
});
