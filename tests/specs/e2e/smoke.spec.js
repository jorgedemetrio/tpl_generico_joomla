// @ts-check
const { test, expect } = require('@playwright/test');
const {
  attachConsole,
  expectNoConsoleProblems,
  expectNoPhpError,
  expectNoRawKeys,
  expectTemplateAssets,
} = require('./_helpers');

/**
 * Smoke E2E do template `generico` contra o Joomla real (localhost:8081/automovel).
 *
 * Para CADA tela representativa do site, garante o contrato do TEMPLATE:
 *   - sem exceção JS / erro de console próprio  -> "se tem erro / se tem warning"
 *   - sem erro PHP no HTML                       -> "se tem erro"
 *   - sem chave de tradução crua                 -> "se falta tradução"
 *   - CSS e JS do template carregados            -> o template realmente aplicou
 *
 * Ruído de TERCEIROS (Pixel, GTM, favicon, etc.) é tolerado por allowlist (ver
 * _helpers.js) — um vermelho aqui aponta problema do template, não do site.
 */

const PAGES = [
  { name: 'home', url: './' },
  { name: 'lojas', url: 'index.php?option=com_automoveis&view=lojas&Itemid=180' },
  { name: 'produtos', url: 'index.php?option=com_automoveis&view=produtos&Itemid=424' },
  { name: 'servicos', url: 'index.php?option=com_automoveis&view=servicos&Itemid=425' },
  { name: 'veiculos', url: 'index.php?option=com_automoveis&view=veiculos&Itemid=181' },
  { name: 'fipe', url: 'index.php?option=com_automoveis&view=fipe&Itemid=427' },
  { name: 'cadastro', url: 'index.php?option=com_automoveis&view=cadastro&Itemid=178' },
  {
    name: 'loja-detalhe',
    url: 'index.php?option=com_automoveis&view=loja&id=borracharia-loja-jornada-1781452299479-sp-sao-paulo&Itemid=427',
  },
  {
    name: 'veiculo-detalhe',
    url: 'index.php?option=com_automoveis&view=veiculo&estabelecimento=borracharia-loja-jornada-1781452299479-sp-sao-paulo&id=carro-chevrolet-argo-1406261551&Itemid=427',
  },
];

for (const p of PAGES) {
  test(`smoke: ${p.name}`, async ({ page }) => {
    const c = attachConsole(page);
    const resp = await page.goto(p.url, { waitUntil: 'load' });
    expect(resp, `sem resposta de ${p.url}`).not.toBeNull();
    expect(resp.status(), `HTTP de ${p.name}`).toBeLessThan(400);
    await page.waitForLoadState('networkidle').catch(() => {});

    expectNoPhpError(await page.content(), p.name);
    await expectTemplateAssets(page);
    await expectNoRawKeys(page, p.name);
    expectNoConsoleProblems(c, p.name);
  });
}
