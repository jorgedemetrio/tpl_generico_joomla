---
name: validacao-pre-producao
description: >-
  Valida o template tpl_generico (Joomla 5) ANTES de entregar na master
  (merge/PR para master) ou de criar a tag v* de deploy. Use quando o usuário
  pedir "validar para master", "validar antes de entregar", "pré-publicação",
  "está pronto para subir?", ou ao mexer em index.php, manifesto, assets ou
  idiomas. Roda o validador automatizado (validar.sh) e aplica o checklist
  manual de responsividade, segurança e performance.
---

# Validação de pré-publicação — tpl_generico (Joomla 5)

Garante que o template atende às regras fundamentais **antes de entregar na
master** e de criar a tag `v*` (que dispara o deploy de produção — ver
`CLAUDE.md` → "Deploy"). Combina um **validador automatizado** com um
**checklist manual** para o que não dá para automatizar com segurança.

## Quando usar

- Antes de **mergear / abrir PR para `master`**.
- Antes de **criar a tag `v<versão>`** de deploy.
- O usuário pede "validar template", "pré-publicação", "está pronto p/ subir".
- Roda na CI em PR para `master` e push na `master`
  (`.github/workflows/validacao-pre-master.yml`). O build geral
  (`build.yml`) roda `php -l` + PHPMD + SonarQube em todo push/PR.

## Como rodar

```bash
.claude/skills/validacao-pre-producao/validar.sh           # tudo (sai != 0 em FAIL)
.claude/skills/validacao-pre-producao/validar.sh --quick   # pula PHPMD
```

O `validar.sh` checa:

1. **Sintaxe PHP** (`php -l`) de todo o template — **FAIL**.
2. **PHPMD** (se instalado) com `phpmd.xml` — **advisory (WARN)**. O ruleset
   exclui `StaticAccess`/`MissingImport` (ruído do core J5, que é chamado
   estaticamente e não é carregado na análise).
3. **i18n — paridade** das chaves nos **8 idiomas** (`.ini` e `.sys.ini`) vs
   pt-BR + `parse_ini_file` — **FAIL** se faltar chave ou não parsear.
4. **Empacotamento**: todo `.php` da raiz do template declarado em `<files>` do
   `templateDetails.xml` — **FAIL** (armadilha clássica: arquivo no ZIP mas fora
   de `<files>` NÃO é instalado → fatal em produção).
5. **`joomla.asset.json`** é JSON válido — **FAIL**.
6. **Posições** usadas no `index.php` declaradas em `<positions>` — WARN.
7. **Convenção Joomla 5**: sem objetos legados `J*` (JFactory, JText, JRoute,
   JHtml, JUri, jimport…) no PHP — WARN. No J5 use as classes COM namespace
   (`Joomla\CMS\Factory`, `HTMLHelper`, `Text`, `Route`, `Uri`, `ModuleHelper`).
8. **`index.html`** em toda pasta de mídia (anti directory listing) — WARN.

**WARN** = heurística, revisar à mão. **FAIL** = bloqueia; corrija antes da tag.

## Checklist manual (o que o validador não cobre)

- **Responsividade (crucial)**: testar desktop/tablet/mobile. O menu mobile
  full-width (posição `mobile-menu`, `#mobileMenuArea`) abre, fecha pelo botão,
  rola quando o conteúdo passa da tela; as sidebars (`sidebar-left`/`-right`)
  **somem** abaixo de `lg` (`d-none d-lg-block`).
- **Performance**: assets via Web Asset Manager (`joomla.asset.json`), logo com
  `loading="eager"`/`fetchpriority="high"`, preconnect dos domínios de tracking.
- **Segurança**: parâmetros `filter="raw"` só editáveis por super admin; saída
  escapada (`htmlspecialchars`) onde for dado do usuário; FTPS com verificação
  de certificado no deploy (`FTP_SSL_VERIFY`).
- **Configurável/flexível**: novas cores/fontes/espaços expostos como parâmetro
  no `templateDetails.xml` e consumidos via variável CSS no `index.php`.

## Princípio de correção

Corrija **pontualmente** (mínima intervenção) e **procure o mesmo padrão** nos
demais arquivos. Ao adicionar chave i18n, adicione nos **8 idiomas**. Ao adicionar
`.php`, declare em `<files>`; ao adicionar CSS/JS, registre em `joomla.asset.json`.

## Fontes da verdade

As regras completas vivem em `CLAUDE.md` (raiz). Mantenha `AGENTS.md`,
`GEMINI.md` e `JULES.md` consistentes ao mudar uma convenção. A versão em
produção é o maior `<version>` em
`https://apps.sobieskiproducoes.com.br/tpl_generico/atualizacao.xml`.
