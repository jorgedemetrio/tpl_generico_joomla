// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Melhoria "Cadastre-se em nossa newsletter": um modal convida visitantes
 * deslogados, no primeiro acesso e após um tempo mínimo no site, a se cadastrar.
 * O recurso vem desligado por padrão (validado no lado PHP/admin) e o texto é
 * configurável. Estes testes exercem o JS e o CSS reais via fixture:
 *   - só aparece depois do tempo configurado (data-delay);
 *   - só aparece uma vez (primeiro acesso) — persiste em localStorage;
 *   - valida o e-mail em JS antes de redirecionar;
 *   - ao enviar um e-mail válido, redireciona para a tela de cadastro com o
 *     e-mail na query string.
 *
 * O tempo "acumulado desde o primeiro acesso" é semeado no localStorage para
 * deixar os testes determinísticos (sem esperar 1 minuto real).
 */

const FIXTURE = '/tests/fixtures/newsletter.html';

/** Semeia o primeiro acesso como tendo ocorrido há ~5 min (tempo já cumprido). */
async function seedEngaged(page) {
  await page.addInitScript(() => {
    try {
      localStorage.setItem('generico_newsletter_first', String(Date.now() - 5 * 60 * 1000));
    } catch (e) {}
  });
}

const modalSel = '#newsletterModal';

test('não aparece antes do tempo mínimo (acesso recém-começado)', async ({ page }) => {
  // Sem semear: o primeiro acesso é "agora", e data-delay=120s não foi atingido.
  await page.goto(FIXTURE);
  await page.waitForTimeout(400); // dá tempo de o JS rodar e (não) abrir
  await expect(page.locator(modalSel)).toBeHidden();
});

test('aparece no primeiro acesso após o tempo configurado', async ({ page }) => {
  await seedEngaged(page);
  await page.goto(FIXTURE);

  const modal = page.locator(modalSel);
  await expect(modal).toBeVisible();
  await expect(modal).toHaveAttribute('aria-modal', 'true');
  await expect(modal).toHaveAttribute('role', 'dialog');
  // O foco vai para o campo de e-mail ao abrir.
  await expect(page.locator('#newsletterModalEmail')).toBeFocused();
});

test('mostra apenas uma vez (primeiro acesso) — não reabre ao recarregar', async ({ page }) => {
  await seedEngaged(page);
  await page.goto(FIXTURE);
  await expect(page.locator(modalSel)).toBeVisible();

  // Recarrega: como já foi exibido, não deve reaparecer.
  await page.reload();
  await page.waitForTimeout(400);
  await expect(page.locator(modalSel)).toBeHidden();
});

test('e-mail inválido: mostra erro e NÃO redireciona', async ({ page }) => {
  await seedEngaged(page);
  await page.goto(FIXTURE);
  await expect(page.locator(modalSel)).toBeVisible();

  await page.fill('#newsletterModalEmail', 'nao-e-email');
  await page.click('.newsletter-modal-submit');

  // Continua na mesma página (sem redirect) e com o erro visível.
  await expect(page).toHaveURL(/newsletter\.html$/);
  await expect(page.locator('#newsletterModalError')).toBeVisible();
  await expect(page.locator('#newsletterModalEmail')).toHaveAttribute('aria-invalid', 'true');
});

test('e-mail válido: redireciona para a tela de cadastro com o e-mail na URL', async ({ page }) => {
  await seedEngaged(page);
  await page.goto(FIXTURE);
  await expect(page.locator(modalSel)).toBeVisible();

  await page.fill('#newsletterModalEmail', 'ana@exemplo.com');
  await page.click('.newsletter-modal-submit');

  await page.waitForURL(/registration-stub\.html\?/);
  expect(page.url()).toContain('email=ana%40exemplo.com');
  await expect(page.locator('#registration-stub')).toBeVisible();
});

test('"Agora não" fecha o modal e ele não volta', async ({ page }) => {
  await seedEngaged(page);
  await page.goto(FIXTURE);
  await expect(page.locator(modalSel)).toBeVisible();

  await page.click('.newsletter-modal-decline');
  await expect(page.locator(modalSel)).toBeHidden();

  // Já foi decidido: não reaparece em uma nova visita.
  await page.reload();
  await page.waitForTimeout(400);
  await expect(page.locator(modalSel)).toBeHidden();
});
