// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Blog de Categoria multi-coluna (com_content): o core do Joomla emite
 *   .blog-items.columns-N  (grid, multi_column_order = 1)  e
 *   .blog-items.masonry-N  (CSS multi-column, multi_column_order = 0, PADRAO)
 * conforme o parametro "Colunas" do item de menu. O template precisa estilizar
 * AMBAS — sem isso o parametro nao surte efeito e os artigos empilham em 1
 * coluna (bug observado em producao). Garantido pelo CSS real do template.
 *
 * Estrategia: contar quantas posicoes horizontais (left) distintas os itens
 * ocupam. 3 colunas => 3 lefts distintos; empilhado => 1.
 */

const FIXTURE = '/tests/fixtures/category-blog.html';

function watchConsole(page) {
  const problems = [];
  page.on('console', (m) => {
    const t = m.type();
    if (t === 'error' || t === 'warning') problems.push(`[${t}] ${m.text()}`);
  });
  page.on('pageerror', (e) => problems.push(`[pageerror] ${e.message}`));
  return problems;
}

// Conta posicoes horizontais distintas dos .blog-item dentro de #containerId.
function distinctColumns(page, containerId) {
  return page.evaluate((id) => {
    const items = Array.from(document.querySelectorAll('#' + id + ' .blog-item'));
    const lefts = new Set(items.map((el) => Math.round(el.getBoundingClientRect().left / 5) * 5));
    return lefts.size;
  }, containerId);
}

test.describe('Blog de Categoria — grid e masonry de 3 colunas', () => {
  test('desktop (>=992px): columns-3 e masonry-3 rendem 3 colunas', async ({ page }) => {
    const problems = watchConsole(page);
    await page.setViewportSize({ width: 1200, height: 900 });
    await page.goto(FIXTURE);

    expect(await distinctColumns(page, 'grid'), 'columns-3 deve ter 3 colunas no desktop').toBe(3);
    expect(await distinctColumns(page, 'masonry'), 'masonry-3 deve ter 3 colunas no desktop').toBe(3);

    // O grid aplica display:grid com 3 trilhas.
    await expect(page.locator('#grid')).toHaveCSS('display', 'grid');

    expect(problems, problems.join('\n')).toEqual([]);
  });

  test('mobile (<768px): ambos colapsam para 1 coluna', async ({ page }) => {
    const problems = watchConsole(page);
    await page.setViewportSize({ width: 375, height: 800 });
    await page.goto(FIXTURE);

    expect(await distinctColumns(page, 'grid'), 'columns-3 deve empilhar em 1 coluna no mobile').toBe(1);
    expect(await distinctColumns(page, 'masonry'), 'masonry-3 deve empilhar em 1 coluna no mobile').toBe(1);

    expect(problems, problems.join('\n')).toEqual([]);
  });

  test('tablet (>=768px e <992px): degrada para 2 colunas', async ({ page }) => {
    watchConsole(page);
    await page.setViewportSize({ width: 820, height: 1000 });
    await page.goto(FIXTURE);

    expect(await distinctColumns(page, 'grid'), 'columns-3 deve ter 2 colunas no tablet').toBe(2);
    expect(await distinctColumns(page, 'masonry'), 'masonry-3 deve ter 2 colunas no tablet').toBe(2);
  });
});
