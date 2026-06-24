// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Texto do menu MOBILE (offcanvas/collapse) bem maior que o padrao do Bootstrap
 * (~1rem/16px), conforme pedido. Garantido pelo CSS real do template:
 *   @media (max-width: 991.98px) {
 *     .navbar-collapse .nav-link / .offcanvas .nav-link ... { font-size: 1.375rem }
 *     .navbar-collapse .dropdown-menu .dropdown-item ...    { font-size: 1.15rem }
 *   }
 * No desktop (>=992px) a regra nao se aplica (menu horizontal volta ao normal).
 */

const FIXTURE = '/tests/fixtures/mobile-menu.html';

function px(page, selector) {
  return page.evaluate(
    (sel) => parseFloat(getComputedStyle(document.querySelector(sel)).fontSize),
    selector
  );
}

test('mobile (<992px): itens do menu ficam bem maiores que o padrao (16px)', async ({ page }) => {
  await page.setViewportSize({ width: 375, height: 800 });
  await page.goto(FIXTURE);

  const topo = await px(page, '#link-sobre');     // item de topo
  const sub  = await px(page, '#sub-web');         // subitem (dropdown)

  // 1.375rem = 22px (topo) e 1.15rem = 18.4px (subitem). Asserimos limiares
  // generosos: o ponto e estar claramente acima do padrao de 16px.
  expect(topo, 'item de topo deve ser bem maior que 16px').toBeGreaterThanOrEqual(20);
  expect(sub, 'subitem deve ser maior que o padrao').toBeGreaterThanOrEqual(17);
  expect(sub, 'subitem deve ser menor que o item de topo (hierarquia)').toBeLessThan(topo);
});

test('desktop (>=992px): a regra mobile NAO se aplica (texto volta ao normal)', async ({ page }) => {
  await page.setViewportSize({ width: 1200, height: 800 });
  await page.goto(FIXTURE);

  const topo = await px(page, '#link-sobre');
  // No desktop nao ha font-size forcado pela regra mobile; fica no padrao (~16px).
  expect(topo, 'no desktop o item de topo nao usa o tamanho mobile').toBeLessThan(20);
});
