// @ts-check
const { test, expect } = require('@playwright/test');
const path = require('path');
const { pathToFileURL } = require('url');

/**
 * Melhoria "Menu": o item de menu da pagina atual deve receber um destaque
 * visual para o usuario saber onde esta. Estes testes validam:
 *   1. O contrato de marcacao dos overrides (.active + aria-current="page").
 *   2. O destaque visual aplicado pelo CSS (cor, peso e indicadores).
 *
 * As fixtures espelham a saida dos overrides e carregam o CSS real do template,
 * entao um teste vermelho aponta uma regressao no override OU no template.css.
 */

/** URL file:// de uma fixture em tests/fixtures. */
function fixture(name) {
  return pathToFileURL(path.join(__dirname, '..', 'fixtures', name)).href;
}

const CTA_RGB = 'rgb(47, 128, 237)'; // --cor-cta (#2F80ED)
const BOLD = '700'; // --peso-fonte-titulos

test.describe('Menu — destaque do item ativo (navbar)', () => {
  test('o item da pagina atual fica destacado e com aria-current', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto(fixture('navbar.html'));

    const active = page.locator('.navbar-nav > li.nav-item.active > a.nav-link').first();

    // Contrato de marcacao.
    await expect(active).toHaveClass(/\bactive\b/);
    await expect(active).toHaveAttribute('aria-current', 'page');

    // Destaque visual: cor CTA + negrito.
    await expect(active).toHaveCSS('color', CTA_RGB);
    await expect(active).toHaveCSS('font-weight', BOLD);

    // Indicador (sublinhado ::after) presente no menu horizontal (desktop).
    const afterHeight = await active.evaluate(
      (el) => getComputedStyle(el, '::after').height
    );
    expect(afterHeight).toBe('3px');
  });

  test('um item inativo NAO recebe destaque', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto(fixture('navbar.html'));

    const inactive = page.locator('a.nav-link', { hasText: 'Sobre' });

    await expect(inactive).not.toHaveClass(/\bactive\b/);
    await expect(inactive).not.toHaveAttribute('aria-current', 'page');

    const weight = await inactive.evaluate((el) => getComputedStyle(el).fontWeight);
    expect(weight).not.toBe(BOLD);

    // Sem indicador ::after no item inativo.
    const afterHeight = await inactive.evaluate(
      (el) => getComputedStyle(el, '::after').height
    );
    expect(afterHeight).not.toBe('3px');
  });

  test('dropdown: o pai e destacado e o filho ativo carrega aria-current', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto(fixture('navbar.html'));

    // Pai do dropdown: destacado (.active), porem NAO e a pagina atual.
    const parent = page.locator('li.nav-item.dropdown > a.nav-link.dropdown-toggle');
    await expect(parent).toHaveClass(/\bactive\b/);
    await expect(parent).not.toHaveAttribute('aria-current', 'page');
    await expect(parent).toHaveCSS('font-weight', BOLD);

    // Filho ativo: aria-current + destaque proprio de item de dropdown.
    const child = page.locator('.dropdown-menu .dropdown-item.active');
    await expect(child).toHaveAttribute('aria-current', 'page');
    await expect(child).toHaveCSS('background-color', CTA_RGB);
    await expect(child).toHaveCSS('color', 'rgb(255, 255, 255)');
  });

  test('mobile (menu empilhado): destaque por barra lateral', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 800 });
    await page.goto(fixture('navbar.html'));

    const active = page.locator('.navbar-nav > li.nav-item.active > a.nav-link').first();
    await expect(active).toHaveCSS('border-left-width', '3px');
    await expect(active).toHaveCSS('border-left-style', 'solid');
    await expect(active).toHaveCSS('border-left-color', CTA_RGB);
  });
});

test.describe('Menu — destaque do item ativo (metismenu)', () => {
  test('o link do item atual fica destacado', async ({ page }) => {
    await page.goto(fixture('metismenu.html'));

    const active = page.locator('.metismenu .active > a').first();
    await expect(active).toHaveCSS('color', CTA_RGB);
    await expect(active).toHaveCSS('font-weight', BOLD);
    await expect(active).toHaveCSS('border-left-width', '3px');
    await expect(active).toHaveCSS('border-left-color', CTA_RGB);
  });

  test('um item inativo do metismenu NAO recebe a barra lateral', async ({ page }) => {
    await page.goto(fixture('metismenu.html'));

    const inactive = page.locator('.metismenu li:not(.active):not(.current) > a', {
      hasText: 'Sobre',
    });
    await expect(inactive).toHaveCSS('border-left-width', '0px');
  });
});
