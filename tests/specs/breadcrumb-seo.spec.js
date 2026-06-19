// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Fase 3 (SEO) — breadcrumb + BreadcrumbList JSON-LD.
 *
 * Não basta "a tela carrega": validamos a REGRA DE NEGÓCIO do dado estruturado
 * que o Google consome (achado C2 do eixo SEO):
 *   - a marcação visual segue o contrato (ol.breadcrumb, último item é a página
 *     atual com aria-current e sem link);
 *   - o JSON-LD é JSON VÁLIDO e é um BreadcrumbList;
 *   - todo "item" é uma URL ABSOLUTA (link relativo desqualifica o rich result);
 *   - o ÚLTIMO ListItem NÃO tem "item" (recomendação do schema.org);
 *   - as posições são sequenciais (1..N).
 * Também falhamos em qualquer erro/warning de console e em chave de tradução
 * crua vazada na tela.
 */

const FIXTURE = '/tests/fixtures/breadcrumb.html';

/** Coleta erros e warnings do console durante a navegação. */
function watchConsole(page) {
  const problems = [];
  page.on('console', (msg) => {
    const type = msg.type();
    if (type === 'error' || type === 'warning') {
      problems.push(`[${type}] ${msg.text()}`);
    }
  });
  page.on('pageerror', (err) => problems.push(`[pageerror] ${err.message}`));
  return problems;
}

test('breadcrumb: sem erro/warning de console e sem chave de tradução crua', async ({ page }) => {
  const problems = watchConsole(page);
  await page.goto(FIXTURE);

  await expect(page.locator('ol.breadcrumb')).toBeVisible();

  // Nenhuma chave de idioma não resolvida vazou para o texto visível.
  const bodyText = await page.locator('body').innerText();
  expect(bodyText).not.toMatch(/TPL_GENERICO_[A-Z0-9_]+/);
  expect(bodyText).not.toMatch(/\bJGLOBAL_[A-Z0-9_]+/);

  expect(problems, problems.join('\n')).toEqual([]);
});

test('breadcrumb: contrato de marcação (último item = página atual, sem link)', async ({ page }) => {
  await page.goto(FIXTURE);

  const items = page.locator('ol.breadcrumb > li.breadcrumb-item');
  await expect(items).toHaveCount(3);

  const last = items.last();
  await expect(last).toHaveClass(/\bactive\b/);
  await expect(last).toHaveAttribute('aria-current', 'page');
  // A página atual não é um link.
  await expect(last.locator('a')).toHaveCount(0);

  // Os itens anteriores são links.
  await expect(items.nth(0).locator('a')).toHaveCount(1);
  await expect(items.nth(1).locator('a')).toHaveCount(1);
});

test('breadcrumb: JSON-LD é um BreadcrumbList válido com URLs absolutas', async ({ page }) => {
  await page.goto(FIXTURE);

  const raw = await page.locator('script[type="application/ld+json"]').first().textContent();
  expect(raw, 'JSON-LD ausente').toBeTruthy();

  // É JSON válido (parse não lança).
  const data = JSON.parse(String(raw));
  expect(data['@type']).toBe('BreadcrumbList');

  const list = data.itemListElement;
  expect(Array.isArray(list)).toBe(true);
  expect(list.length).toBe(3);

  list.forEach((node, idx) => {
    expect(node['@type']).toBe('ListItem');
    expect(node.position).toBe(idx + 1); // posições sequenciais 1..N
    expect(typeof node.name).toBe('string');
    expect(node.name.length).toBeGreaterThan(0);

    if (idx < list.length - 1) {
      // Itens intermediários: "item" presente e ABSOLUTO.
      expect(typeof node.item).toBe('string');
      expect(node.item).toMatch(/^https?:\/\//);
    } else {
      // Último (página atual): SEM "item".
      expect(node.item).toBeUndefined();
    }
  });
});
