// @ts-check
const { test, expect } = require('@playwright/test');
const fs = require('fs');

/**
 * Aba "Manutenção" das opções do template (backup/atualização no admin).
 *
 * A fixture admin-configtools.html espelha o markup emitido por
 * fields/genericoconfigtools.php dentro do formulário de estilo e carrega o
 * JS REAL (media/js/admin-configtools.js). Validamos o contrato completo:
 *  - Exportar: o JSON gerado (download e "Ver JSON") reflete o estado ATUAL
 *    dos inputs jform[params][*] e traz os metadados (template, versão);
 *  - Importar: preenche o formulário, ignora chaves desconhecidas, rejeita
 *    arquivo de outro template e NUNCA salva sozinho (só via Salvar);
 *  - Atualizar: consulta update.ajax do com_installer (interceptado) e, ao
 *    confirmar, submete POST para update.update com cid[] e o token CSRF.
 */

const FIXTURE = '/tests/fixtures/admin-configtools.html';

/** Importa um objeto como se fosse um arquivo .json escolhido no upload. */
async function importJson(page, data) {
  await page.setInputFiles('[data-gct-file]', {
    name: 'backup.json',
    mimeType: 'application/json',
    buffer: Buffer.from(JSON.stringify(data)),
  });
}

test.describe('Manutenção — exportar configurações', () => {
  test('"Ver JSON" mostra o estado atual do formulário com metadados', async ({ page }) => {
    await page.goto(FIXTURE);
    // Valor alterado e ainda NÃO salvo também entra no backup (estado visível).
    await page.fill('[name="jform[params][gtmId]"]', 'GTM-NOVO');
    await page.click('[data-gct-action="view"]');

    const box = page.locator('[data-gct-json]');
    await expect(box).toBeVisible();
    const data = JSON.parse(await box.inputValue());

    expect(data.template).toBe('generico');
    expect(data.templateVersion).toBe('1.0.2');
    expect(data.type).toBe('tpl_generico:settings');
    expect(data.params.primaryColor).toBe('#1F4E79');
    expect(data.params.gtmId).toBe('GTM-NOVO');
    // Radio: exporta apenas o valor marcado do grupo.
    expect(data.params.layoutWidth).toBe('boxed');
    expect(data.params.verticalSpacing).toBe('M');

    // Segundo clique recolhe a visualização (toggle).
    await page.click('[data-gct-action="view"]');
    await expect(box).toBeHidden();
  });

  test('"Exportar" baixa um .json válido com os parâmetros', async ({ page }) => {
    await page.goto(FIXTURE);
    const [download] = await Promise.all([
      page.waitForEvent('download'),
      page.click('[data-gct-action="export"]'),
    ]);

    expect(download.suggestedFilename()).toMatch(/^generico-config-.+\.json$/);
    const data = JSON.parse(fs.readFileSync(await download.path(), 'utf8'));
    expect(data.template).toBe('generico');
    expect(data.params.primaryColor).toBe('#1F4E79');
    expect(data.params.stickyHeader).toBe('1');
  });
});

test.describe('Manutenção — importar configurações', () => {
  test('preenche o formulário, reporta aplicados e ignora chaves desconhecidas', async ({ page }) => {
    await page.goto(FIXTURE);
    await importJson(page, {
      type: 'tpl_generico:settings',
      template: 'generico',
      templateVersion: '1.0.2',
      params: {
        primaryColor: '#123456',
        layoutWidth: 'full-width',
        verticalSpacing: 'L',
        customHeadCode: '<meta name="x" content="y">',
        naoExiste: 'abc',
      },
    });

    await expect(page.locator('[name="jform[params][primaryColor]"]')).toHaveValue('#123456');
    await expect(page.locator('[name="jform[params][layoutWidth]"][value="full-width"]')).toBeChecked();
    await expect(page.locator('[name="jform[params][verticalSpacing]"]')).toHaveValue('L');
    await expect(page.locator('[name="jform[params][customHeadCode]"]')).toHaveValue('<meta name="x" content="y">');

    const report = page.locator('[data-gct-report]');
    await expect(report).toBeVisible();
    await expect(report).toContainText('4 parâmetro(s) carregado(s)');
    await expect(report).toContainText('naoExiste');
    // Nada é submetido automaticamente: a página continua no formulário.
    expect(page.url()).toContain('admin-configtools.html');
  });

  test('avisa quando o arquivo veio de outra versão do template', async ({ page }) => {
    await page.goto(FIXTURE);
    await importJson(page, {
      template: 'generico',
      templateVersion: '0.9.0',
      params: { primaryColor: '#000000' },
    });
    await expect(page.locator('[data-gct-report]')).toContainText(
      'exportado da versão 0.9.0; a instalada é 1.0.2'
    );
  });

  test('rejeita JSON de outro template sem tocar no formulário', async ({ page }) => {
    await page.goto(FIXTURE);
    await importJson(page, { template: 'cassiopeia', params: { primaryColor: '#FF0000' } });

    const report = page.locator('[data-gct-report]');
    await expect(report).toBeVisible();
    await expect(report).toContainText('não é um JSON de configurações válido');
    await expect(page.locator('[name="jform[params][primaryColor]"]')).toHaveValue('#1F4E79');
  });

  test('rejeita arquivo que não é JSON', async ({ page }) => {
    await page.goto(FIXTURE);
    await page.setInputFiles('[data-gct-file]', {
      name: 'lixo.json',
      mimeType: 'application/json',
      buffer: Buffer.from('isto não é json'),
    });
    await expect(page.locator('[data-gct-report]')).toContainText(
      'não é um JSON de configurações válido'
    );
  });
});

test.describe('Manutenção — atualização pelo com_installer', () => {
  // O update.ajax do com_installer responde um ARRAY CRU — VERIFICADO num Joomla
  // 6.1.1 real: `[]` sem atualização, `[{update_id, version, ...}]` quando há.
  // NÃO é um envelope `{data: [...]}`. Os mocks abaixo usam o formato real; um
  // mock envelopado mascararia o bug de ler só `json.data` (some com o botão).
  test('sem atualização pendente (array vazio): já está na última versão', async ({ page }) => {
    await page.route(/task=update\.ajax/, (route) =>
      route.fulfill({ contentType: 'application/json', body: JSON.stringify([]) })
    );
    await page.goto(FIXTURE);
    await page.click('[data-gct-action="check"]');

    await expect(page.locator('[data-gct-update-status]')).toContainText('já está na versão mais recente');
    await expect(page.locator('[data-gct-action="update"]')).toBeHidden();
  });

  test('com atualização (array cru): mostra a versão e o POST vai com cid[] + token', async ({ page }) => {
    await page.route(/task=update\.ajax/, (route) =>
      route.fulfill({ contentType: 'application/json', body: JSON.stringify([{ update_id: 42, version: '9.9.9' }]) })
    );
    let postData = null;
    await page.route(/task=update\.update/, (route) => {
      postData = route.request().postData();
      return route.fulfill({ contentType: 'text/html', body: '<p>update ok</p>' });
    });

    await page.goto(FIXTURE);
    await page.click('[data-gct-action="check"]');

    await expect(page.locator('[data-gct-update-status]')).toContainText('Nova versão disponível: 9.9.9');
    const updateBtn = page.locator('[data-gct-action="update"]');
    await expect(updateBtn).toBeVisible();

    // Confirma o dialog e acompanha a navegação do POST para o com_installer.
    page.on('dialog', (dialog) => dialog.accept());
    await Promise.all([page.waitForURL(/task=update\.update/), updateBtn.click()]);

    expect(postData).toContain('cid%5B%5D=42');
    expect(postData).toContain('testtoken=1');
  });

  test('também aceita o formato envelopado {data:[...]} (compat entre versões)', async ({ page }) => {
    await page.route(/task=update\.ajax/, (route) =>
      route.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: [{ update_id: 7, version: '2.0.0' }] }) })
    );
    await page.goto(FIXTURE);
    await page.click('[data-gct-action="check"]');

    await expect(page.locator('[data-gct-update-status]')).toContainText('Nova versão disponível: 2.0.0');
    await expect(page.locator('[data-gct-action="update"]')).toBeVisible();
  });

  test('cancelar o confirm não dispara o update', async ({ page }) => {
    await page.route(/task=update\.ajax/, (route) =>
      route.fulfill({ contentType: 'application/json', body: JSON.stringify([{ update_id: 42, version: '9.9.9' }]) })
    );
    let posted = false;
    await page.route(/task=update\.update/, (route) => {
      posted = true;
      return route.fulfill({ contentType: 'text/html', body: 'ok' });
    });

    await page.goto(FIXTURE);
    await page.click('[data-gct-action="check"]');
    page.on('dialog', (dialog) => dialog.dismiss());
    await page.click('[data-gct-action="update"]');

    await page.waitForTimeout(300);
    expect(posted).toBe(false);
    expect(page.url()).toContain('admin-configtools.html');
  });

  test('HTTP 200 com {success:false}: trata como erro, não como "atualizado"', async ({ page }) => {
    await page.route(/task=update\.ajax/, (route) =>
      route.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: false, message: 'boom', data: [] }) })
    );
    await page.goto(FIXTURE);
    await page.click('[data-gct-action="check"]');

    await expect(page.locator('[data-gct-update-status]')).toContainText('Não foi possível consultar as atualizações');
    await expect(page.locator('[data-gct-action="update"]')).toBeHidden();
  });

  test('erro na consulta (HTTP 500): orienta a usar o gerenciador de atualizações', async ({ page }) => {
    await page.route(/task=update\.ajax/, (route) => route.fulfill({ status: 500, body: 'err' }));
    await page.goto(FIXTURE);
    await page.click('[data-gct-action="check"]');
    await expect(page.locator('[data-gct-update-status]')).toContainText(
      'Não foi possível consultar as atualizações'
    );
  });
});
