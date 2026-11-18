// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Layout com as duas sidebars (index.php) — regressão de responsividade.
 *
 * Bug: o template.css fixava `.col-lg-3 { width: 280px }` e deixava a área
 * principal em `col-lg-6` (50%). Entre 992px e ~1120px, 50% + 2x280px passava de
 * 100% e a sidebar direita quebrava para BAIXO do conteúdo. Além disso a regra
 * atingia qualquer `.col-lg-3` colocado pelo editor dentro de um artigo.
 *
 * Agora só #sidebar-left/-right têm largura fixa e #component-area ocupa o resto.
 */

const FIXTURE = '/tests/fixtures/sidebars-layout.html';

async function box(page, sel) {
  const b = await page.locator(sel).boundingBox();
  if (!b) throw new Error('sem box: ' + sel);
  return b;
}

for (const width of [992, 1024, 1100, 1199, 1280, 1440]) {
  test(`${width}px: sidebars e conteúdo ficam lado a lado, sem estourar a largura`, async ({ page }) => {
    await page.setViewportSize({ width, height: 800 });
    await page.goto(FIXTURE);

    const left = await box(page, '#sidebar-left');
    const main = await box(page, '#component-area');
    const right = await box(page, '#sidebar-right');

    // Mesma linha (topo alinhado) — a sidebar direita nao quebrou para baixo.
    expect(Math.abs(right.y - main.y)).toBeLessThan(2);
    expect(Math.abs(left.y - main.y)).toBeLessThan(2);
    // Ordem esquerda -> centro -> direita, sem sobreposicao.
    expect(left.x + left.width).toBeLessThanOrEqual(main.x + 1);
    expect(main.x + main.width).toBeLessThanOrEqual(right.x + 1);
    // Sidebars com a largura fixa do template; conteudo com espaco util.
    expect(Math.round(left.width)).toBe(280);
    expect(Math.round(right.width)).toBe(280);
    expect(main.width).toBeGreaterThan(300);
    // Nada de rolagem horizontal da pagina.
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(0);
  });
}

test('um .col-lg-3 dentro do artigo NÃO é travado em 280px', async ({ page }) => {
  await page.setViewportSize({ width: 1280, height: 800 });
  await page.goto(FIXTURE);
  const col = await box(page, '#article-col');
  const row = await box(page, '#article-grid');
  // 25% da linha do artigo (grade Bootstrap normal), nao 280px fixos.
  expect(Math.abs(col.width - row.width * 0.25)).toBeLessThan(row.width * 0.03);
  expect(Math.round(col.width)).not.toBe(280);
});

test('abaixo de 992px as sidebars ficam ocultas e o conteúdo ocupa a largura toda', async ({ page }) => {
  await page.setViewportSize({ width: 800, height: 800 });
  await page.goto(FIXTURE);
  await expect(page.locator('#sidebar-left')).toBeHidden();
  await expect(page.locator('#sidebar-right')).toBeHidden();
  const main = await box(page, '#component-area');
  const container = await box(page, '.container');
  expect(main.width).toBeGreaterThan(container.width * 0.5);
});
