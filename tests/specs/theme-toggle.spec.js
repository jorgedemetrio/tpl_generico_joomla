// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Alternância de tema claro/escuro (index.php #themeToggle + template.js::
 * initThemeToggle), incluindo o contrato da chave de persistência (#81).
 *
 * Regra de negócio (não basta "carregar"): o botão reflete o tema atual (ícone
 * lua/sol + aria-pressed); ao clicar, alterna data-bs-theme, persiste a escolha
 * no localStorage e — ao recarregar — o tema escolhido é restaurado antes da
 * pintura (sem flash). A chave do localStorage vem de data-theme-key (fonte
 * única PHP<->JS); o JS deve persistir EXATAMENTE sob essa chave, não sob um
 * literal embutido — é o que o achado #81 exige e este teste comprova.
 *
 * Cada teste roda num contexto isolado (localStorage limpo).
 */

const FIXTURE = '/tests/fixtures/theme-toggle.html';
const toggle = '#themeToggle';

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

/** Lê o valor salvo sob a chave declarada em data-theme-key. */
async function storedTheme(page) {
  return page.evaluate(() => {
    const k = document.documentElement.getAttribute('data-theme-key') || 'generico-theme';
    return localStorage.getItem(k);
  });
}

test('estado inicial reflete o tema claro (lua, aria-pressed=false) — sem erro/warning/chave crua', async ({ page }) => {
  const problems = watchConsole(page);
  await page.goto(FIXTURE);

  await expect(page.locator('html')).toHaveAttribute('data-bs-theme', 'light');
  await expect(page.locator(toggle)).toHaveAttribute('aria-pressed', 'false');
  await expect(page.locator(toggle + ' i')).toHaveClass(/fa-moon/);

  const text = await page.locator('body').innerText();
  expect(text).not.toMatch(/TPL_GENERICO_[A-Z0-9_]+/);
  expect(problems, problems.join('\n')).toEqual([]);
});

test('clicar alterna para escuro: data-bs-theme, ícone (sol), aria-pressed e persistência', async ({ page }) => {
  const problems = watchConsole(page);
  await page.goto(FIXTURE);

  await page.click(toggle);

  await expect(page.locator('html')).toHaveAttribute('data-bs-theme', 'dark');
  await expect(page.locator(toggle)).toHaveAttribute('aria-pressed', 'true');
  await expect(page.locator(toggle + ' i')).toHaveClass(/fa-sun/);
  // #81: persistido sob a chave declarada em data-theme-key (não um literal solto).
  expect(await storedTheme(page)).toBe('dark');

  expect(problems, problems.join('\n')).toEqual([]);
});

test('clicar de novo volta para claro e atualiza a persistência', async ({ page }) => {
  await page.goto(FIXTURE);

  await page.click(toggle); // -> dark
  await expect(page.locator('html')).toHaveAttribute('data-bs-theme', 'dark');
  await page.click(toggle); // -> light

  await expect(page.locator('html')).toHaveAttribute('data-bs-theme', 'light');
  await expect(page.locator(toggle)).toHaveAttribute('aria-pressed', 'false');
  await expect(page.locator(toggle + ' i')).toHaveClass(/fa-moon/);
  expect(await storedTheme(page)).toBe('light');
});

test('a escolha persiste entre páginas: ao recarregar, o tema escuro é restaurado', async ({ page }) => {
  await page.goto(FIXTURE);
  await page.click(toggle); // escolhe escuro
  await expect(page.locator('html')).toHaveAttribute('data-bs-theme', 'dark');

  await page.reload();

  // O bootstrap inline restaura o tema salvo antes da pintura; o botão reflete.
  await expect(page.locator('html')).toHaveAttribute('data-bs-theme', 'dark');
  await expect(page.locator(toggle)).toHaveAttribute('aria-pressed', 'true');
  await expect(page.locator(toggle + ' i')).toHaveClass(/fa-sun/);
});
