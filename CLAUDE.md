# CLAUDE.md

Orientações para o Claude Code trabalhar neste repositório.

## Regras de colaboração (OBRIGATÓRIO)

- **Trabalhe somente dentro de `tpl_generico_joomla`.** Não crie arquivos nem pastas fora do projeto (sem `/tmp`, sem worktrees externos, sem scratch em outros diretórios).
- **Nunca rode `git commit` nem `git push` sem o usuário pedir explicitamente.** Empurrar uma tag `v*` dispara um deploy de produção automático (ver seção Deploy). Prepare as mudanças, mostre os comandos prontos e espere o "ok".
- **A pasta `tpl_generico/` é o pacote.** É ela que é zipada e instalada em produção. Faça as correções diretamente nos arquivos dela.
- **Respeite o processo de empacotamento do Joomla:** o conserto é sempre via pacote ZIP instalável (Extensões → Gerenciar → Instalar → Enviar Pacote), nunca editando arquivo solto no servidor de produção.

## O projeto

- Template de site para **Joomla 5** chamado `generico` (instala em `templates/generico/`). Requer **PHP 8.1+**, alvo `joomla 5.*`.
- Documentação técnica detalhada (estrutura, CSS/variáveis, Web Asset Manager, posições de módulo, deploy): **`docs/DEVELOPMENT_GUIDELINES.md`** — leia antes de alterar.

## Empacotamento Joomla — armadilhas críticas

- **`templateDetails.xml` `<files>` precisa listar TODO arquivo a ser instalado.** O instalador do Joomla copia apenas o que está declarado em `<files>` (mais as `<folder>`). Um arquivo que está no ZIP mas **fora** do `<files>` NÃO é instalado.
  - Incidente real: `index.php` fazia `require_once helper.php`, o `helper.php` ia no ZIP, mas não estava no `<files>` → não era instalado → `Failed to open stream: helper.php` → erro fatal em produção. Pior: o auto-update nunca corrigia, porque todo pacote publicado tinha o mesmo defeito.
  - Ao adicionar qualquer `.php` novo (ex.: um helper), **declare em `<files>`**. Se for CSS/JS, registre também em `joomla.asset.json`.
- **Assets (`css/`, `js/`, `images/`) — inconsistência conhecida (pendente):** as pastas estão na raiz do template, mas o manifesto declara `<media destination="templates/site/generico" folder="media">` (o Joomla procura uma pasta `media/` no pacote que não existe) e as URIs do `joomla.asset.json` não batem (`template.css` vs `css/template.css`; `offline.css` não existe no repo). Numa **instalação limpa** esses assets não são copiados. O padrão Joomla 5 é assets em `media/templates/site/generico/` com as URIs do `joomla.asset.json` relativas a essa pasta. Numa reinstalação por cima de um install existente os assets antigos sobrevivem, então o estilo não quebra imediatamente.

## Deploy e versionamento

- **Deploy automático:** criar a tag `v<versão>` (ex.: `v1.0.15`) dispara `.github/workflows/deploy.yml` → `.github/scripts/deploy.sh`, que monta o ZIP, atualiza a versão a partir da tag, gera o `atualizacao.xml` e envia via FTPS para o servidor de update `apps.sobieskiproducoes.com.br/tpl_generico/`. O site puxa o update por esse feed (`<updateservers>` no `templateDetails.xml`).
- **Tags devem ser minúsculas `v*`.** O filtro do workflow é `v*` (sensível a maiúsculas); tags com `V` maiúsculo (ex.: `V1.0.11`, `V10.0.11`) são ignoradas e só poluem o repositório. Não criar tags malformadas (ex.: versão "10").
- A versão é injetada pela tag no `templateDetails.xml`. Bug conhecido (cosmético): o `sed` que ajusta a versão no `joomla.asset.json` não casa o padrão, então não atualiza lá — a versão que o Joomla usa para o update vem do `templateDetails.xml`, que é atualizado certo.

## Convenções de código

- **Overrides** do Joomla ficam em `/html` — nunca editar o core do Joomla.
- **Tema por variáveis CSS** (`--cor-*`, etc.) geradas no `index.php` a partir dos parâmetros do `templateDetails.xml`; estilos em `css/template.css` (sem SASS/SCSS).
- **Assets** via Web Asset Manager: `$wa->usePreset('tpl_generico.preset')`, com tudo registrado no `joomla.asset.json`.
- **Idiomas** em `language/<tag>/` (en-GB, pt-BR, es-ES, de-DE, fr-FR, it-IT, ja-JP, zh-CN).
- Sem ambiente de homologação formal: testar localmente (responsividade desktop/tablet/mobile + regressão) antes de versionar.
- Qualidade de código monitorada por SonarQube, PHP Syntax Check (`php -l`) e PHPMD (PHP Mess Detector). 
- O fluxo de CI (`build.yml`) valida todos os arquivos PHP do repositório.
- **Testes de UI (Playwright)** ficam em `tests/` na **raiz do repositório** (fora de `tpl_generico/`, então não entram no ZIP). Rodam com fixtures estáticas que espelham a saída dos overrides + o CSS real, sem precisar de Joomla. Ao mexer em CSS/overrides, atualize a fixture/spec correspondente. Detalhes em `tests/README.md` e em `docs/DEVELOPMENT_GUIDELINES.md` (5.4).
- A versão final em produção pode ser consultada em: `https://apps.sobieskiproducoes.com.br/tpl_generico/atualizacao.xml` (buscando o maior valor da tag `<version>`).
