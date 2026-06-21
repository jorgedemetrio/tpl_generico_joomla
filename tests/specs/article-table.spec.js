// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Responsividade mobile (F1): tabelas e blocos <pre> largos dentro de um artigo
 * devem rolar na HORIZONTAL dentro do próprio bloco — não estourar a largura da
 * página no celular (sensação de layout quebrado). Garantido pelo CSS real:
 *   .com-content-article__body table/pre { display:block; max-width:100%; overflow-x:auto }
 *
 * O teste roda num viewport de celular, com conteúdo intrinsecamente mais largo
 * que a tela (string longa sem espaços). Sem a regra, a página inteira rolaria
 * na horizontal — é exatamente isso que verificamos NÃO acontecer.
 */

const FIXTURE = '/tests/fixtures/article-table.html';

test.use({ viewport: { width: 375, height: 700 } });

function watchConsole(page) {
  const problems = [];
  page.on('console', (m) => {
    const t = m.type();
    if (t === 'error' || t === 'warning') problems.push(`[${t}] ${m.text()}`);
  });
  page.on('pageerror', (e) => problems.push(`[pageerror] ${e.message}`));
  return problems;
}

test('tabela e <pre> largos rolam na horizontal sem estourar a página (mobile)', async ({ page }) => {
  const problems = watchConsole(page);
  await page.goto(FIXTURE);

  const table = page.locator('#wide-table');
  const pre = page.locator('#wide-pre');

  // O CSS do template foi aplicado (bloco rolável).
  await expect(table).toHaveCSS('display', 'block');
  await expect(table).toHaveCSS('overflow-x', 'auto');
  await expect(pre).toHaveCSS('overflow-x', 'auto');

  // A PÁGINA não rola na horizontal (o conteúdo largo ficou contido).
  const pageOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth - document.documentElement.clientWidth
  );
  expect(pageOverflow, 'a página não deve rolar horizontalmente').toBeLessThanOrEqual(1);

  // A tabela e o <pre>, em si, têm conteúdo mais largo que sua caixa e rolam
  // internamente — provando que o estouro foi contido pelo overflow, não por
  // quebra/corte do conteúdo.
  const tableRolls = await page.evaluate(() => {
    const t = document.getElementById('wide-table');
    return t.scrollWidth > t.clientWidth;
  });
  const preRolls = await page.evaluate(() => {
    const p = document.getElementById('wide-pre');
    return p.scrollWidth > p.clientWidth;
  });
  expect(tableRolls, 'a tabela deve rolar internamente').toBe(true);
  expect(preRolls, 'o <pre> deve rolar internamente').toBe(true);

  expect(problems, problems.join('\n')).toEqual([]);
});
