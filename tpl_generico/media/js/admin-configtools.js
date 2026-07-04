/**
 * tpl_generico — ferramentas de manutencao na edicao do estilo (admin).
 *
 * Carregado pelo campo custom `fields/genericoconfigtools.php` (aba
 * "Manutencao" das opcoes do template). Vanilla JS, sem jQuery.
 *
 * Funcionalidades:
 *  - Exportar: coleta os valores atuais de TODOS os inputs jform[params][*]
 *    do formulario de estilo e baixa (ou exibe) um JSON com metadados
 *    (template, versao, data) — backup antes de desinstalar/reinstalar.
 *  - Importar: le um arquivo JSON exportado, valida que pertence a este
 *    template e preenche o formulario. NAO salva sozinho: o usuario revisa
 *    e clica em Salvar (os params so vao ao banco pelo fluxo normal).
 *  - Atualizar: consulta o com_installer do proprio Joomla (task update.ajax,
 *    com token CSRF) para descobrir se ha versao nova no servidor de update
 *    e, ao confirmar, submete um POST para task update.update — o CORE faz o
 *    download/troca de arquivos e preserva os estilos, sem codigo do template
 *    rodando durante a copia (nada de script se apagando no meio do processo).
 *
 * O bloco <script type="application/json" data-gct-config> embutido pelo campo
 * fornece URLs, token e textos ja traduzidos — este arquivo nao depende de
 * Joomla.Text nem de outros scripts do admin.
 */
(function () {
  'use strict';

  // Substitui cada %s da string traduzida pelo proximo argumento, em ordem.
  function sprintf(text) {
    const args = Array.prototype.slice.call(arguments, 1);
    let i = 0;
    return String(text).replace(/%s/g, function () {
      return i < args.length ? String(args[i++]) : '';
    });
  }

  // Extrai o nome do parametro de um name="jform[params][chave]".
  function paramKey(name) {
    const m = /^jform\[params\]\[([^\]]+)\]$/.exec(name || '');
    return m ? m[1] : null;
  }

  // Mapa { chave: valor } com o estado ATUAL do formulario (inclui alteracoes
  // ainda nao salvas — o que o usuario ve e o que sai no backup).
  function collectParams(form) {
    const params = {};
    form.querySelectorAll('[name^="jform[params]["]').forEach(function (el) {
      const key = paramKey(el.getAttribute('name'));
      if (!key) {
        return;
      }
      if (el.type === 'radio') {
        if (el.checked) {
          params[key] = el.value;
        }
        return;
      }
      params[key] = el.value;
    });
    return params;
  }

  function buildExport(cfg, form) {
    return {
      type: 'tpl_generico:settings',
      template: cfg.template,
      templateVersion: cfg.version || '',
      exportedAt: new Date().toISOString(),
      params: collectParams(form)
    };
  }

  function downloadJson(data) {
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const link = document.createElement('a');
    // 2026-07-04T12-30 -> nome de arquivo ordenavel e sem caracteres invalidos.
    const stamp = data.exportedAt.slice(0, 16).replace(/:/g, '-');
    link.href = URL.createObjectURL(blob);
    link.download = data.template + '-config-' + stamp + '.json';
    document.body.appendChild(link);
    link.click();
    setTimeout(function () {
      URL.revokeObjectURL(link.href);
      link.remove();
    }, 1000);
  }

  function fireChange(el) {
    // 'input' + 'change' para o showon do Joomla e mascaras reagirem.
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  // Aplica um valor ao(s) elemento(s) de um mesmo name. Retorna false quando o
  // valor nao e aplicavel (radio/select sem opcao correspondente).
  function setFieldValue(els, value) {
    const first = els[0];
    const text = value === null || value === undefined ? '' : String(value);
    if (first.type === 'radio') {
      let matched = null;
      els.forEach(function (radio) {
        radio.checked = radio.value === text;
        if (radio.checked) {
          matched = radio;
        }
      });
      if (!matched) {
        return false;
      }
      fireChange(matched);
      return true;
    }
    if (first.tagName === 'SELECT') {
      const has = Array.prototype.some.call(first.options, function (opt) {
        return opt.value === text;
      });
      if (!has) {
        return false;
      }
      first.value = text;
      fireChange(first);
      return true;
    }
    first.value = text;
    fireChange(first);
    return true;
  }

  // Preenche o formulario com os params importados; separa aplicados de
  // ignorados (chave inexistente nesta versao ou valor sem opcao equivalente).
  function applyParams(form, params) {
    const result = { applied: [], ignored: [] };
    Object.keys(params).forEach(function (key) {
      // So nomes de parametro validos entram no seletor (JSON e entrada externa).
      if (!/^[A-Za-z0-9_]+$/.test(key)) {
        result.ignored.push(key);
        return;
      }
      const els = form.querySelectorAll('[name="jform[params][' + key + ']"]');
      if (!els.length || !setFieldValue(els, params[key])) {
        result.ignored.push(key);
        return;
      }
      result.applied.push(key);
    });
    return result;
  }

  function isValidImport(cfg, data) {
    return !!data && typeof data === 'object' && !Array.isArray(data) &&
      data.template === cfg.template &&
      !!data.params && typeof data.params === 'object' && !Array.isArray(data.params);
  }

  // tone: sufixo Bootstrap (success/warning/danger/secondary).
  function notify(el, tone, message) {
    if (!el) {
      return;
    }
    el.className = 'mt-2 small alert alert-' + tone + ' py-2';
    el.style.whiteSpace = 'pre-line';
    el.textContent = message;
    el.hidden = false;
  }

  function handleImportText(ui, text) {
    let data = null;
    try {
      data = JSON.parse(text);
    } catch (e) {
      data = null;
    }
    if (!isValidImport(ui.cfg, data)) {
      notify(ui.report, 'danger', ui.cfg.text.importInvalid);
      return;
    }
    const result = applyParams(ui.form, data.params);
    const lines = [sprintf(ui.cfg.text.importApplied, result.applied.length)];
    if (result.ignored.length) {
      lines.push(sprintf(ui.cfg.text.importIgnored, result.ignored.join(', ')));
    }
    if (data.templateVersion && ui.cfg.version && data.templateVersion !== ui.cfg.version) {
      lines.push(sprintf(ui.cfg.text.versionDiff, data.templateVersion, ui.cfg.version));
    }
    notify(ui.report, result.ignored.length ? 'warning' : 'success', lines.join('\n'));
  }

  // Consulta as atualizacoes pendentes deste template no com_installer
  // (mesmo endpoint usado pelo icone "Atualizacoes de extensoes" do painel).
  function checkUpdate(ui) {
    notify(ui.updateStatus, 'secondary', ui.cfg.text.checking);
    fetch(ui.cfg.ajaxUrl, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }
        return response.json();
      })
      .then(function (json) {
        const list = (json && json.data) || [];
        if (!list.length) {
          ui.pendingUpdate = null;
          if (ui.updateBtn) {
            ui.updateBtn.hidden = true;
          }
          notify(ui.updateStatus, 'success', ui.cfg.text.uptodate);
          return;
        }
        ui.pendingUpdate = list[0];
        if (ui.updateBtn) {
          ui.updateBtn.hidden = false;
        }
        notify(ui.updateStatus, 'warning', sprintf(ui.cfg.text.available, list[0].version || '?'));
      })
      .catch(function () {
        notify(ui.updateStatus, 'danger', ui.cfg.text.updateError);
      });
  }

  function hiddenInput(name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    return input;
  }

  // Submete o update pelo fluxo padrao do com_installer (POST + token CSRF).
  // Navega para a pagina de resultado do instalador — feedback nativo do Joomla.
  function runUpdate(ui) {
    if (!ui.pendingUpdate || !window.confirm(ui.cfg.text.confirm)) {
      return;
    }
    const form = document.createElement('form');
    form.method = 'post';
    form.action = ui.cfg.updateUrl;
    form.appendChild(hiddenInput('cid[]', String(ui.pendingUpdate.update_id)));
    form.appendChild(hiddenInput(ui.cfg.token, '1'));
    document.body.appendChild(form);
    form.submit();
  }

  function on(ui, action, handler) {
    const btn = ui.root.querySelector('[data-gct-action="' + action + '"]');
    if (btn) {
      btn.addEventListener('click', handler);
    }
  }

  function bind(ui) {
    on(ui, 'export', function () {
      downloadJson(buildExport(ui.cfg, ui.form));
    });
    on(ui, 'view', function () {
      if (ui.jsonBox.hidden) {
        ui.jsonBox.value = JSON.stringify(buildExport(ui.cfg, ui.form), null, 2);
      }
      ui.jsonBox.hidden = !ui.jsonBox.hidden;
    });
    on(ui, 'import', function () {
      ui.fileInput.value = '';
      ui.fileInput.click();
    });
    ui.fileInput.addEventListener('change', function () {
      const file = ui.fileInput.files && ui.fileInput.files[0];
      if (!file) {
        return;
      }
      file.text()
        .then(function (text) {
          handleImportText(ui, text);
        })
        .catch(function () {
          notify(ui.report, 'danger', ui.cfg.text.importReadError);
        });
    });
    if (ui.cfg.ajaxUrl) {
      on(ui, 'check', function () {
        checkUpdate(ui);
      });
      on(ui, 'update', function () {
        runUpdate(ui);
      });
    }
  }

  function init(root) {
    const cfgEl = root.querySelector('[data-gct-config]');
    const form = root.closest('form');
    if (!cfgEl || !form) {
      return;
    }
    let cfg = null;
    try {
      cfg = JSON.parse(cfgEl.textContent);
    } catch (e) {
      return;
    }
    bind({
      root: root,
      form: form,
      cfg: cfg,
      jsonBox: root.querySelector('[data-gct-json]'),
      report: root.querySelector('[data-gct-report]'),
      fileInput: root.querySelector('[data-gct-file]'),
      updateBtn: root.querySelector('[data-gct-action="update"]'),
      updateStatus: root.querySelector('[data-gct-update-status]'),
      pendingUpdate: null
    });
  }

  function boot() {
    document.querySelectorAll('[data-gct-root]').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
