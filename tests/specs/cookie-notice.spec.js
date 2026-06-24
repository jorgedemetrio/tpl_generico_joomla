// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Aviso de cookies — fluxo funcional (não só "carrega").
 *
 * Regra de negócio: o aviso só aparece para quem ainda não consentiu; é aceito
 * no clique OU automaticamente após `data-timeout` segundos; ao aceitar, grava
 * o cookie `generico_cookie_consent` e NÃO volta a aparecer em visitas seguintes.
 * Este teste exercita o template.js real (caminho refatorado na Fase 4:
 * intAttr/byId/forEach) — uma regressão no parse do timeout ou no consentimento
 * quebra aqui. Cada teste roda num contexto limpo (sem cookies prévios).
 */

const FIXTURE = '/tests/fixtures/cookie-notice.html';
const COOKIE = 'generico_cookie_consent';
const notice = '#cookieNotice';

function watchConsole(page) {
  const problems = [];
  page.on('console', (m) => {
    const t = m.type();
    if (t === 'error' || t === 'warning') problems.push(`[${t}] ${m.text()}`);
  });
  page.on('pageerror', (e) => problems.push(`[pageerror] ${e.message}`));
  return problems;
}

async function hasConsentCookie(page) {
  const cookies = await page.context().cookies();
  return cookies.some((c) => c.name === COOKIE && c.value === '1');
}

test('aparece quando ainda não há consentimento (sem erro/warning/chave crua)', async ({ page }) => {
  const problems = watchConsole(page);
  await page.goto(FIXTURE);

  const el = page.locator(notice);
  await expect(el).toBeVisible(); // perde [hidden] e ganha .is-visible
  await expect(el).toHaveClass(/\bis-visible\b/);
  // Ainda não consentiu: cookie não existe.
  expect(await hasConsentCookie(page)).toBe(false);

  const text = await page.locator('body').innerText();
  expect(text).not.toMatch(/TPL_GENERICO_[A-Z0-9_]+/);
  expect(problems, problems.join('\n')).toEqual([]);
});

test('botão Aceitar grava o consentimento, fecha e não reaparece ao recarregar', async ({ page }) => {
  await page.goto(FIXTURE);
  await expect(page.locator(notice)).toBeVisible();

  await page.click('#cookieAccept');

  // O cookie de consentimento é gravado e o aviso some.
  await expect.poll(() => hasConsentCookie(page)).toBe(true);
  await expect(page.locator(notice)).toBeHidden();

  // Em uma nova visita (com o cookie presente) o aviso não volta.
  await page.reload();
  await page.waitForTimeout(300);
  await expect(page.locator(notice)).toBeHidden();
});

test('auto-aceite após data-timeout: fecha sozinho e grava o cookie', async ({ page }) => {
  await page.goto(FIXTURE);
  await expect(page.locator(notice)).toBeVisible();

  // data-timeout="2": some sozinho em ~2s (contagem regressiva no template.js).
  await expect(page.locator(notice)).toBeHidden({ timeout: 6000 });
  expect(await hasConsentCookie(page)).toBe(true);
});
