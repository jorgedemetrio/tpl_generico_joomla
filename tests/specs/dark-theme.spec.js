// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Modo escuro (data-bs-theme="dark") na ORDEM REAL de producao.
 *
 * Regressao: o helper.php imprime `:root { --cor-texto: ...; }` inline DEPOIS do
 * template.css; esse seletor tem a mesma especificidade de [data-bs-theme="dark"],
 * entao o :root claro vencia e o "escuro" ficava com texto escuro sobre fundo
 * claro (e links/titulos/rodape ilegiveis). O template.css agora usa
 * :root[data-bs-theme="dark"] (especificidade maior, vence em qualquer ordem).
 *
 * Alem de "as cores trocaram", validamos o CONTRASTE (WCAG AA) dos pares que
 * antes falhavam: titulo (cor primaria escura), link, rodape e erro.
 */

const FIXTURE = '/tests/fixtures/dark-theme.html';

/** Converte a cor computada em [r,g,b] 0-255. O Chrome devolve "rgb(...)" para
 *  cores simples, mas "color(srgb 0.39 0.63 0.95)" (componentes 0-1) quando o valor
 *  vem de color-mix() — os dois formatos precisam ser entendidos. */
function toRgb255(color) {
  const nums = color.match(/[\d.]+/g).slice(0, 3).map(Number);
  return color.startsWith('color(') ? nums.map((v) => v * 255) : nums;
}

/** Luminancia relativa (WCAG). */
function luminance(color) {
  const [r, g, b] = toRgb255(color).map((v) => {
    const c = v / 255;
    return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
  });
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function contrast(a, b) {
  const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);
  return (hi + 0.05) / (lo + 0.05);
}

async function css(page, sel, prop) {
  return page.locator(sel).evaluate((el, p) => getComputedStyle(el)[p], prop);
}

test('as variáveis escuras vencem o :root inline do helper (ordem de produção)', async ({ page }) => {
  await page.goto(FIXTURE);
  const bg = await css(page, 'body', 'backgroundColor');
  const fg = await css(page, 'body', 'color');
  // Fundo escuro e texto claro — nao o #FFF/#222 do :root inline.
  expect(luminance(bg)).toBeLessThan(0.05);
  expect(luminance(fg)).toBeGreaterThan(0.6);
  expect(contrast(fg, bg)).toBeGreaterThanOrEqual(7);
});

test('títulos e links têm contraste AA sobre o fundo escuro', async ({ page }) => {
  await page.goto(FIXTURE);
  const bg = await css(page, 'body', 'backgroundColor');
  expect(contrast(await css(page, '#titulo', 'color'), bg)).toBeGreaterThanOrEqual(4.5);
  expect(contrast(await css(page, '#link', 'color'), bg)).toBeGreaterThanOrEqual(4.5);
});

test('cartão e topbar usam a superfície escura (sem o bg-light claro do Bootstrap)', async ({ page }) => {
  await page.goto(FIXTURE);
  const card = await css(page, '#card', 'backgroundColor');
  const top = await css(page, '#topbar', 'backgroundColor');
  expect(luminance(card)).toBeLessThan(0.1);
  expect(luminance(top)).toBeLessThan(0.1);
  expect(contrast(await css(page, '#topbar-text', 'color'), top)).toBeGreaterThanOrEqual(4.5);
});

test('rodapé: título, texto e links legíveis (antes ficavam escuro-sobre-escuro)', async ({ page }) => {
  await page.goto(FIXTURE);
  const bg = await css(page, '#footer', 'backgroundColor');
  for (const sel of ['#footer-titulo', '#footer-texto', '#footer-link']) {
    expect(contrast(await css(page, sel, 'color'), bg), sel).toBeGreaterThanOrEqual(4.5);
  }
});

test('mensagem de erro do formulário tem contraste AA no escuro', async ({ page }) => {
  await page.goto(FIXTURE);
  const bg = await css(page, 'body', 'backgroundColor');
  expect(contrast(await css(page, '#erro', 'color'), bg)).toBeGreaterThanOrEqual(4.5);
});

test('no tema claro nada muda: texto escuro sobre fundo claro', async ({ page }) => {
  await page.goto(FIXTURE);
  await page.evaluate(() => document.documentElement.setAttribute('data-bs-theme', 'light'));
  const bg = await css(page, 'body', 'backgroundColor');
  const fg = await css(page, 'body', 'color');
  expect(luminance(bg)).toBeGreaterThan(0.9);
  expect(luminance(fg)).toBeLessThan(0.05);
  expect(contrast(await css(page, '#titulo', 'color'), bg)).toBeGreaterThanOrEqual(4.5);
});
