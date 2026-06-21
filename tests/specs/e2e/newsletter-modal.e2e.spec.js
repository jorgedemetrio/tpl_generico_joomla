// @ts-check
const { test, expect } = require('@playwright/test');
const { adminLogin, setTemplateStyleParams } = require('./_admin');
const { attachConsole, expectNoConsoleProblems } = require('./_helpers');

/**
 * Fluxo funcional REAL do modal de newsletter contra o Joomla — incluindo o
 * lado do ADMIN (o recurso vem DESLIGADO por padrão).
 *
 * Setup/teardown (admin): habilita newsletterModal=1 antes e restaura =0 depois,
 * deixando o site como estava. Regra de negócio coberta:
 *  - só aparece no 1º acesso e APÓS o tempo mínimo (data-delay) — semeamos o
 *    "primeiro acesso" no passado p/ determinismo (sem esperar 1 min real);
 *  - é um diálogo acessível (role/aria) e foca o campo de e-mail ao abrir;
 *  - e-mail inválido → erro inline, sem redirecionar;
 *  - e-mail válido → redireciona para o cadastro (com_users) com o e-mail na URL;
 *  - "Agora não" fecha e não reabre (mostrado uma única vez).
 *
 * Exige ADMIN_USER/ADMIN_PASS no ambiente.
 */

const HOME = './';
const modalSel = '#newsletterModal';

/** Semeia o "primeiro acesso" há ~5 min (tempo mínimo já cumprido). */
async function seedEngaged(page) {
  await page.addInitScript(() => {
    try {
      localStorage.setItem('generico_newsletter_first', String(Date.now() - 5 * 60 * 1000));
    } catch (e) {}
  });
}

test.describe.serial('newsletter modal (E2E real)', () => {
  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage();
    await adminLogin(page);
    await setTemplateStyleParams(page, { newsletterModal: 1, newsletterModalDelay: 60 });
    await page.close();
  });

  test.afterAll(async ({ browser }) => {
    const page = await browser.newPage();
    await adminLogin(page);
    await setTemplateStyleParams(page, { newsletterModal: 0 }); // restaura desligado
    await page.close();
  });

  test('NÃO aparece antes do tempo mínimo (acesso recém-começado)', async ({ page }) => {
    await page.goto(HOME, { waitUntil: 'load' }); // sem semear: 1º acesso = agora
    await page.waitForTimeout(900);
    await expect(page.locator(modalSel)).toBeHidden();
  });

  test('aparece após o tempo (1º acesso): diálogo acessível e foco no e-mail', async ({ page }) => {
    const c = attachConsole(page);
    await seedEngaged(page);
    await page.goto(HOME, { waitUntil: 'load' });

    const modal = page.locator(modalSel);
    await expect(modal).toBeVisible();
    await expect(modal).toHaveAttribute('aria-modal', 'true');
    await expect(modal).toHaveAttribute('role', 'dialog');
    await expect(page.locator('#newsletterModalEmail')).toBeFocused();

    expectNoConsoleProblems(c, 'newsletter abrir');
  });

  test('e-mail inválido: mostra erro inline e NÃO redireciona', async ({ page }) => {
    await seedEngaged(page);
    await page.goto(HOME, { waitUntil: 'load' });
    await expect(page.locator(modalSel)).toBeVisible();

    await page.fill('#newsletterModalEmail', 'nao-e-email');
    await page.click('.newsletter-modal-submit');
    await page.waitForTimeout(400);

    await expect(page.locator('#newsletterModalError')).toBeVisible();
    await expect(page.locator('#newsletterModalEmail')).toHaveAttribute('aria-invalid', 'true');
    expect(page.url(), 'não deve navegar para o cadastro').not.toContain('view=registration');
  });

  test('e-mail válido: redireciona para o cadastro (com_users) com o e-mail na URL', async ({ page }) => {
    await seedEngaged(page);
    await page.goto(HOME, { waitUntil: 'load' });
    await expect(page.locator(modalSel)).toBeVisible();

    await page.fill('#newsletterModalEmail', 'ana@exemplo.com');
    const [req] = await Promise.all([
      page.waitForRequest(
        (r) => /option=com_users/.test(r.url()) && /email=ana(%40|@)exemplo\.com/i.test(r.url())
      ),
      page.click('.newsletter-modal-submit'),
    ]);
    expect(req.url()).toContain('option=com_users');
  });

  test('"Agora não" fecha e não reabre ao recarregar (mostrado uma única vez)', async ({ page }) => {
    await seedEngaged(page);
    await page.goto(HOME, { waitUntil: 'load' });
    await expect(page.locator(modalSel)).toBeVisible();

    await page.click('.newsletter-modal-decline');
    await expect(page.locator(modalSel)).toBeHidden();

    await page.reload({ waitUntil: 'load' });
    await page.waitForTimeout(900);
    await expect(page.locator(modalSel)).toBeHidden();
  });
});
