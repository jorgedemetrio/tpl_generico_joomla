// @ts-check
const { test, expect } = require('@playwright/test');
const { attachConsole, expectNoConsoleProblems } = require('./_helpers');

/**
 * Fluxo funcional REAL do tema claro/escuro contra o Joomla (não fixture).
 *
 * Regra de negócio (não basta "carregar"):
 *  - o botão reflete o tema atual (ícone lua/sol + aria-pressed);
 *  - ao clicar, alterna data-bs-theme, troca o ícone e PERSISTE no localStorage
 *    sob a chave declarada em data-theme-key (fonte única PHP<->JS, achado #81);
 *  - a escolha PERSISTE ao navegar para OUTRA página do site (home -> listagem),
 *    restaurada antes da pintura — é o que diferencia o E2E real do teste de
 *    fixture (exercita o bootstrap inline em páginas distintas do componente).
 */

const HOME = './';
const OTHER = 'index.php?option=com_automoveis&view=lojas&Itemid=180';
const toggle = '#themeToggle';

const iconFor = (theme) => (theme === 'dark' ? /fa-sun/ : /fa-moon/);
const pressedFor = (theme) => (theme === 'dark' ? 'true' : 'false');

async function storedTheme(page) {
  return page.evaluate(() => {
    const k = document.documentElement.getAttribute('data-theme-key') || 'generico-theme';
    return localStorage.getItem(k);
  });
}

async function currentTheme(page) {
  return page.locator('html').getAttribute('data-bs-theme');
}

test('tema: alterna, persiste no storage e sobrevive à navegação entre páginas', async ({ page }) => {
  const c = attachConsole(page);

  // Estado base com localStorage limpo (default do admin decide light/dark).
  await page.goto(HOME, { waitUntil: 'load' });
  await page.evaluate(() => {
    const k = document.documentElement.getAttribute('data-theme-key') || 'generico-theme';
    localStorage.removeItem(k);
  });
  await page.reload({ waitUntil: 'load' });

  const base = await currentTheme(page);
  expect(base, 'data-bs-theme inicial deve ser light/dark').toMatch(/^(light|dark)$/);
  await expect(page.locator(toggle)).toBeVisible();
  await expect(page.locator(toggle)).toHaveAttribute('aria-pressed', pressedFor(base));
  await expect(page.locator(toggle + ' i')).toHaveClass(iconFor(base));

  // Alterna para o tema oposto.
  const flipped = base === 'dark' ? 'light' : 'dark';
  await page.click(toggle);
  await expect(page.locator('html')).toHaveAttribute('data-bs-theme', flipped);
  await expect(page.locator(toggle)).toHaveAttribute('aria-pressed', pressedFor(flipped));
  await expect(page.locator(toggle + ' i')).toHaveClass(iconFor(flipped));
  expect(await storedTheme(page), 'persistido sob a chave de data-theme-key').toBe(flipped);

  // Navega para OUTRA página: o tema escolhido deve ser restaurado.
  await page.goto(OTHER, { waitUntil: 'load' });
  await expect(page.locator('html')).toHaveAttribute('data-bs-theme', flipped);
  await expect(page.locator(toggle)).toHaveAttribute('aria-pressed', pressedFor(flipped));
  await expect(page.locator(toggle + ' i')).toHaveClass(iconFor(flipped));

  expectNoConsoleProblems(c, 'fluxo de tema');
});
