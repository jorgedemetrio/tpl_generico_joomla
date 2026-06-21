// @ts-check
const { test, expect } = require('@playwright/test');
const { attachConsole, expectNoConsoleProblems } = require('./_helpers');

/**
 * Performance/SEO — logo do cabeçalho reserva altura (anti-CLS, F2).
 *
 * Regra: quando a URL do logo traz as dimensões intrínsecas (joomlaImage
 * `?width=W&height=H`), o template (TplGenericoHelper::buildLogo) deve emitir
 * `height` proporcional ao `width` configurado e `style: height: <n>px` (NÃO
 * `auto`), para o navegador reservar o espaço e não deslocar o layout ao
 * carregar a imagem (Cumulative Layout Shift). A imagem continua do mesmo
 * tamanho visual; só o espaço passa a ser reservado.
 */

test('logo do header reserva altura proporcional (anti-CLS)', async ({ page }) => {
  const c = attachConsole(page);
  await page.goto('./', { waitUntil: 'domcontentloaded' });

  const img = page.locator('.navbar-brand img').first();
  await expect(img, 'logo deve ser uma imagem no header').toHaveCount(1);

  const width = Number(await img.getAttribute('width'));
  const height = Number(await img.getAttribute('height'));
  const style = (await img.getAttribute('style')) || '';
  const src = (await img.getAttribute('src')) || '';

  const dims = src.match(/[?&]width=(\d+)[\s\S]*?[?&]height=(\d+)/);
  test.skip(!dims, 'logo sem dimensões intrínsecas na URL — height auto é aceitável');

  expect(width, 'width numérico').toBeGreaterThan(0);
  expect(height, 'height reservado (não auto)').toBeGreaterThan(0);
  expect(style, 'style não deve usar height:auto').not.toMatch(/height:\s*auto/i);
  expect(style, 'style deve reservar height em px').toMatch(/height:\s*\d+px/i);

  // A altura reservada respeita a proporção intrínseca (±1px de arredondamento).
  const iw = Number(dims[1]);
  const ih = Number(dims[2]);
  const expectedH = Math.round((width * ih) / iw);
  expect(Math.abs(height - expectedH), `height ${height} ~ esperado ${expectedH}`).toBeLessThanOrEqual(1);

  expectNoConsoleProblems(c, 'logo-cls');
});
