// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Botão "voltar ao topo" (index.php #backToTop + template.js::initBackToTop).
 *
 * Regra de negócio (não basta "carregar"): em páginas LONGAS no desktop o botão
 * aparece depois que o usuário rola um pouco (> 60% da altura visível) e, ao
 * clicar, leva de volta ao topo; no celular ele não aparece (lá a barra inferior
 * cumpre o papel). Exercita o caminho real (rafThrottle/byId, agora com const).
 *
 * Cada teste roda num contexto isolado.
 */

const FIXTURE = '/tests/fixtures/back-to-top.html';
const btnSel = '#backToTop';

/** Acumula erros/warnings de console e erros de página durante o teste. */
function watchConsole(page) {
  const problems = [];
  page.on('console', (m) => {
    const t = m.type();
    if (t === 'error' || t === 'warning') problems.push(`[${t}] ${m.text()}`);
  });
  page.on('pageerror', (e) => problems.push(`[pageerror] ${e.message}`));
  return problems;
}

test('no topo da página o botão fica oculto (sem erro/warning/chave crua)', async ({ page }) => {
  const problems = watchConsole(page);
  await page.goto(FIXTURE);

  await expect(page.locator(btnSel)).toBeHidden(); // visibility:hidden até rolar

  const text = await page.locator('body').innerText();
  expect(text).not.toMatch(/TPL_GENERICO_[A-Z0-9_]+/);
  expect(problems, problems.join('\n')).toEqual([]);
});

test('em página longa no desktop, aparece ao rolar para baixo', async ({ page }) => {
  await page.goto(FIXTURE);
  await expect(page.locator(btnSel)).toBeHidden();

  // Rola bem além de 60% da altura visível.
  await page.evaluate(() => window.scrollTo(0, 1200));

  await expect(page.locator(btnSel)).toBeVisible();
  await expect(page.locator(btnSel)).toHaveClass(/is-visible/);
});

test('clicar no botão leva de volta ao topo', async ({ page }) => {
  await page.goto(FIXTURE);
  await page.evaluate(() => window.scrollTo(0, 1200));
  await expect(page.locator(btnSel)).toBeVisible();

  await page.click(btnSel);

  // O scroll volta ao topo (a animação suave conclui em instantes).
  await expect.poll(() => page.evaluate(() => Math.round(window.scrollY))).toBeLessThan(50);
  await expect(page.locator(btnSel)).toBeHidden();
});

test('no celular (viewport estreito) o botão não aparece, mesmo rolando', async ({ page }) => {
  await page.setViewportSize({ width: 400, height: 800 });
  await page.goto(FIXTURE);

  await page.evaluate(() => window.scrollTo(0, 1200));
  // Largura < 768px => não elegível; permanece oculto.
  await page.waitForTimeout(200);
  await expect(page.locator(btnSel)).toBeHidden();
});
