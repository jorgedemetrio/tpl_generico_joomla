# Diretrizes de Desenvolvimento - Template tpl_generico

## 1. Introdução

Este documento fornece as diretrizes técnicas para o desenvolvimento e manutenção do template `tpl_generico` para Joomla 5. O objetivo é garantir a consistência, qualidade e facilidade de manutenção do projeto.

O público-alvo são desenvolvedores Joomla que já possuem familiaridade com o CMS, mas que precisam entender as especificidades deste template.

## 2. Estrutura de Arquivos

O `tpl_generico` segue a estrutura padrão de templates do Joomla. Os arquivos e diretórios mais importantes para o desenvolvimento estão localizados na raiz do template (`tpl_generico/`) e são descritos abaixo:

-   `templateDetails.xml`: O arquivo de manifesto do template. Define os metadados (versão, autor), as posições de módulo, os parâmetros de configuração (cores, fontes, etc.) e todos os arquivos/pastas que compõem o template. É também aqui que o servidor de atualização é configurado.

-   `index.php`: O arquivo principal que renderiza a estrutura HTML do site. Ele carrega os assets (CSS/JS), gera as variáveis de CSS a partir dos parâmetros, e renderiza as posições de módulo e o componente principal.

-   `joomla.asset.json`: Define os arquivos de CSS e JavaScript do template para que possam ser gerenciados pelo Web Asset Manager do Joomla. É a forma moderna de registrar e carregar assets no Joomla 5.

-   `/css`: Contém as folhas de estilo do template.
    -   `template.css`: O principal arquivo de estilos. As customizações de CSS devem ser feitas aqui.

-   `/js`: Contém os arquivos JavaScript do template.
    -   `template.js`: O principal arquivo de scripts (JavaScript puro/vanilla). Funcionalidades customizadas devem ser adicionadas aqui.

-   `/html`: Este diretório é usado para overrides (sobrescritas) de views de componentes e módulos do Joomla. Por exemplo, `html/com_content/article/default.php` sobrescreveria a view padrão de um artigo.

-   `/language`: Contém os arquivos de tradução do template para diferentes idiomas. As strings de texto usadas no template (visíveis no admin ou no frontend) são definidas aqui.

-   `/images`: Diretório para armazenar as imagens estáticas utilizadas pelo template.

-   `error.php`: Arquivo que renderiza as páginas de erro (ex: 404 - Não Encontrado).

-   `offline.php`: Página mostrada quando o site está no modo "Offline" no Joomla.

## 3. Desenvolvimento Frontend

Esta seção detalha como modificar a aparência e o comportamento do template.

### 3.1. Estilos (CSS)

O template utiliza um sistema moderno e flexível de variáveis CSS (CSS Custom Properties) para gerenciar a aparência visual.

**Como Funciona:**
1.  As cores, fontes e espaçamentos são definidos como parâmetros no `templateDetails.xml`.
2.  No `index.php`, os valores desses parâmetros são lidos e injetados diretamente no HTML como variáveis CSS no escopo `:root`.
3.  O arquivo `css/template.css` utiliza essas variáveis (ex: `color: var(--cor-primaria);`) para aplicar os estilos.

**Para Customizar Estilos:**
-   **Não há SASS/SCSS.** As edições devem ser feitas diretamente no arquivo `css/template.css`.
-   **Utilize as variáveis existentes** sempre que possível para manter a consistência com os parâmetros do template.
-   Para adicionar novos estilos, siga a estrutura de componentes já existente no arquivo (ex: `/* Component: Footer */`).

### 3.2. Scripts (JavaScript)

O template carrega o JavaScript pelo Web Asset Manager do Joomla (não via CDN):
-   **Bootstrap 5.3.3**: O framework completo de componentes e plugins.
-   **jQuery NÃO é carregado.** O Joomla 5 não embarca jQuery e este template também não — o `template.js` é JavaScript puro (vanilla). Se um módulo/extensão precisar de jQuery, ele deve carregá-lo por conta própria.

**Arquivo de Scripts Customizados:**
-   As funcionalidades específicas do template devem ser adicionadas em `js/template.js`.
-   Atualmente, este arquivo contém um script em JavaScript puro (vanilla JS) para ajustar o espaçamento do header fixo.
-   Escreva código novo em JavaScript puro (vanilla), seguindo o padrão do `template.js` (sem dependência de jQuery).

### 3.3. Gerenciamento de Assets (`joomla.asset.json`)

O Joomla 5 utiliza um sistema de "Web Asset Manager" para carregar os arquivos CSS e JS de forma otimizada.

-   O arquivo `joomla.asset.json` é o responsável por "registrar" os assets do template.
-   Ele define os nomes dos assets (`tpl_generico.css`, `tpl_generico.js`) e suas dependências (ex: `core`, `fontawesome`).
-   No `index.php`, a linha `$wa->usePreset('tpl_generico.preset');` carrega o conjunto de CSS e JS definidos no preset do `joomla.asset.json`.

Se você precisar adicionar um novo arquivo CSS ou JS ao template, lembre-se de registrá-lo primeiro em `joomla.asset.json` antes de tentar carregá-lo no `index.php`.

## 4. Gerenciamento de Módulos

### 4.1. Posições de Módulo

O template oferece uma ampla gama de posições de módulo para flexibilizar a organização do conteúdo. As posições são renderizadas usando a grade (grid) do Bootstrap 5.

**Posições Disponíveis:**
```
- topbar
- below-top
- menu
- mobile-menu
- search
- banner
- top-a
- top-b
- main-top
- main-bottom
- breadcrumbs
- sidebar-left
- sidebar-right
- bottom-a
- bottom-b
- bottom
- bottom-nav
- footer
- debug
- message
- error-403
- error-404
```

**Como Usar:**
1. No painel de administração do Joomla, vá para **Sistema > Módulos do Site**.
2. Crie ou edite um módulo.
3. No campo "Posição", selecione uma das posições disponíveis do template `generico`.

### 4.2. Menu mobile full-width e sidebars (responsividade)

Em telas pequenas (abaixo do breakpoint `lg`, 992px) o template adota um menu **full-width** dedicado, em vez de espremer menus laterais:

-   **Posição `mobile-menu`**: coloque aqui **apenas o(s) módulo(s)** que devem aparecer no botão de menu móvel (ex.: o módulo de Menu principal). O conteúdo é renderizado dentro de `#mobileMenuArea`, um *offcanvas* que ocupa **toda a largura e altura** da tela, com **botão de fechar** no topo e **rolagem interna** quando o conteúdo passa da altura visível.
-   **Sidebars desktop-only**: `sidebar-left` e `sidebar-right` usam `d-none d-lg-block` — **somem abaixo de `lg`**. Em mobile elas seriam empilhadas e ocupariam a tela inteira; por isso a navegação móvel vai para a posição `mobile-menu`. No desktop voltam como colunas fixas (e *sticky*).
-   O botão `mobile-menu` (ícone `fa-bars`) só aparece abaixo de `lg`. A posição `menu` continua existindo para o menu principal, com comportamento mobile próprio configurável (`offcanvas`/`collapse`) — use uma ou outra conforme o layout para não duplicar botões.

### 4.3. Recursos de UI/acessibilidade (header, rodapé e navegação)

-   **Rodapé responsivo**: as colunas usam `col-12 col-sm-6 col-lg-N` — empilham (1/linha) no celular, 2/linha em tablet pequeno e abrem nas N colunas configuradas (`footerColumns`) só a partir de `lg`.
-   **Skip link** (`Pular para o conteúdo`): primeiro elemento focável do `<body>`, aponta para `#main-content` (que recebe `tabindex="-1"`). Fica invisível até receber foco (`.visually-hidden-focusable`).
-   **Toggle de tema claro/escuro**: parâmetro `themeToggle` (Funcional, padrão ligado) exibe um botão no header. A escolha é persistida em `localStorage` (`generico-theme`) e aplicada antes da pintura por um script inline no `<head>` (sem flash). Integra-se ao `colorScheme` (inclusive `auto`).
-   **Sidebars `sticky` seguras**: o `template.js` mede a coluna `.sidebar-content`; se for mais alta que a área visível, marca `.is-tall` e o CSS devolve a coluna ao fluxo normal (rola junto com a página), evitando o último item inalcançável.
-   **`bottom-nav`**: posição de módulo renderizada como barra **fixa inferior** apenas em telas `< md` (`d-md-none`). Coloque ali, por exemplo, um módulo de menu de ações. O `<body>` ganha `padding-bottom` quando há módulo nessa posição (classe `has-bottom-nav`).
-   **Voltar ao topo**: botão `#backToTop` que o `template.js` só exibe fora do mobile (largura ≥ 768px) e em páginas longas (altura do documento > 2× a viewport).
-   **Skeleton shimmer**: imagens com `loading="lazy"` recebem um brilho animado (CSS) até carregarem; o `template.js` adiciona `.is-loaded` no `load`. Respeita `prefers-reduced-motion`.
-   **Fonte de marca**: o padrão de `fontFamilyPrimary` inicia com `'Inter'` e o `googleFontUrl` já vem com a URL do Inter (`display=swap`). Tipografia fluida (`clamp()`) cobre `h1`–`h6`.
-   **Destaque do menu ativo (“você está aqui”)**: o item de menu da página atual recebe destaque visual para o usuário se localizar.
    -   **Marcação** — o override `html/mod_menu/default.php` (navbar principal) detecta o item atual pelo sinal canônico do `mod_menu` (`$active_id` e `$path`, os mesmos que o layout `dropdown-metismenu` usa), aplica a classe `active` ao `<li>` **e** ao `<a class="nav-link active">`, e adiciona `aria-current="page"` ao link da página atual (para leitores de tela). Quando o item atual é filho de um **dropdown**, o item-pai também é destacado (`.active`), mas **sem** `aria-current` — só o filho é, de fato, a página. O layout `dropdown-metismenu` (posições offcanvas/sidebar) já marca o `<li>` com `active`/`current` pelo core.
    -   **Estilo** — em `css/template.css` (seção *Menu ativo*), o item ativo fica sempre em **negrito** (`font-weight: 700`) e usa a cor **configurável** `--cor-menu-ativo` (parâmetro `menuActiveColor` no admin → fieldset *Aparência*; se vazio, assume o padrão `#2F80ED`, o azul do CTA). O `helper.php` gera `--cor-menu-ativo` e `--cor-menu-ativo-rgb` (tripla para o leve fundo `rgba`). O destaque aparece como **sublinhado** (`::after`) no menu horizontal do desktop (`≥ 992px`) e como **barra à esquerda + leve fundo** no menu empilhado do mobile (`< 992px`) e no metismenu. Funciona nos temas claro e escuro.
    -   Há testes Playwright cobrindo esse comportamento, inclusive a cor configurável (ver 5.4).

## 5. Testes e Boas Práticas

### 5.1. Fluxo de Teste

Como não há um ambiente de homologação formal, todo desenvolvimento deve ser testado localmente antes de ser enviado ao repositório.

1.  **Ambiente Local:** Mantenha uma instalação do Joomla 5 funcional em seu ambiente de desenvolvimento local.
2.  **Verificação Visual:** Após qualquer alteração de CSS ou layout, verifique o site em diferentes resoluções de tela (desktop, tablet, mobile) para garantir a responsividade.
3.  **Verificação Funcional:** Teste todas as funcionalidades alteradas ou adicionadas. Por exemplo, se modificar o menu, teste a navegação, os submenus e o comportamento no mobile.
4.  **Teste de Regressão:** Navegue pelas principais áreas do site (home, artigos, formulários) para garantir que sua alteração não quebrou outras funcionalidades.

### 5.2. Boas Práticas

-   **Padrões de Código:** Mantenha os padrões de código existentes. No CSS, use a nomenclatura de componentes e as variáveis CSS. No PHP, siga as convenções do Joomla.
-   **Comentários:** Comente seções de código complexas ou que não sejam óbvias.
-   **Overrides:** Para modificar a saída de um módulo ou componente do Joomla, utilize o sistema de `overrides` no diretório `/html` em vez de editar os arquivos do core do Joomla.
-   **Commits:** Escreva mensagens de commit claras e descritivas.

### 5.3. Validação automatizada (qualidade e padronização)

Antes de entregar na master ou criar a tag `v*`, rode o validador local:

```bash
.claude/skills/validacao-pre-producao/validar.sh
```

Ele **bloqueia (FAIL)** em: erro de `php -l`, falta de paridade i18n nos 8 idiomas (`.ini`/`.sys.ini`), `.php` da raiz não declarado em `<files>` do manifesto e `joomla.asset.json` inválido. Gera **WARN** (revisar à mão) para PHPMD, posições não declaradas, uso de objetos legados `J*` (no Joomla 5 use as classes COM namespace: `Joomla\CMS\Factory`, `HTMLHelper`, `Text`, `Route`, `Uri`, `ModuleHelper`) e pastas de mídia sem `index.html`.

Na CI:

-   **`build.yml`** (todo push/PR): `php -l` em todo `.php`, PHPMD (`phpmd.xml`, não-bloqueante) e SonarQube.
-   **`validacao-pre-master.yml`** (PR/push para `master`): roda o `validar.sh` e **bloqueia o merge** em qualquer FAIL.

Foco da validação: **performance**, **segurança**, **configurável/flexível** e, sobretudo, **responsividade**. A versão atual em produção é o maior `<version>` em `https://apps.sobieskiproducoes.com.br/tpl_generico/atualizacao.xml`.

### 5.4. Testes de UI automatizados (Playwright)

Comportamentos de interface que o `php -l`/SonarQube não pegam são cobertos por testes [Playwright](https://playwright.dev/) na pasta **`tests/`** (na raiz do repositório, **fora** de `tpl_generico/` — não vão para o ZIP de produção). Veja [`tests/README.md`](../tests/README.md).

**Como rodam (sem precisar de Joomla):** as *fixtures* HTML em `tests/fixtures/` **espelham a marcação** gerada pelos overrides (ex.: `html/mod_menu/default.php`) e referenciam o **CSS real** (`media/css/template.css`); as specs em `tests/specs/` abrem a fixture via `file://` e validam o **contrato de marcação** (`.active`, `aria-current="page"`) e o **estilo computado** (cor, peso, indicadores). Um teste vermelho aponta regressão no override **ou** no CSS.

```bash
cd tests
npm install
npx playwright install chromium   # primeira vez
npm test
```

**Ao alterar UI:** se mudar a saída de um override, **atualize a fixture correspondente** (ela é o contrato) e adicione/ajuste asserts na spec. O workflow `.github/workflows/playwright.yml` roda essas specs em pushes/PRs que tocam o CSS, os overrides de menu ou a pasta `tests/`.

Cobertura atual: **destaque do item de menu ativo** (navbar desktop/mobile, dropdown e metismenu) — ver 4.3.

## 6. Deploy e Atualizações

O deploy de novas versões do template é um processo automatizado acionado pela criação de uma nova **tag** no GitHub. O processo utiliza as GitHub Actions para construir o pacote e enviá-lo para o servidor de atualizações.

### 6.1. Estrutura do XML de Atualização

Para cada nova versão, um arquivo `atualizacao.xml` deve ser gerado no servidor com a seguinte estrutura. A pipeline de CI/CD se encarregará de substituir as variáveis `<NOME_APP>` e `<VERSAO_DA_TAG>`.

```xml
<?xml version="1.0" encoding="utf-8"?>
<updates>
    <update>
        <name>tpl_generico</name>
        <description>Template Genérico para Joomla 5</description>
        <element>generico</element>
        <type>template</type>
        <version><VERSAO_DA_TAG></version>
        <infourl title="Sobieski Produções">http://apps.sobieskiproducoes.com.br/tpl_generico/atualizacao.xml</infourl>
        <downloads>
            <downloadurl type="full" format="zip">http://apps.sobieskiproducoes.com.br/tpl_generico/tpl_generico-<VERSAO_DA_TAG>.zip</downloadurl>
        </downloads>
        <tags>
            <tag>stable</tag>
        </tags>
        <maintainer>Jorge Demetrio</maintainer>
        <maintainerurl>https://www.sobieskiproducoes.com.br</maintainerurl>
        <targetplatform name="joomla" version="5.*"/>
        <php_minimum>8.1</php_minimum>
    </update>
</updates>
```

### 6.2. Ajuste no XML de Instalação do Template

O arquivo `templateDetails.xml` deve conter o bloco `<updateservers>` apontando para o `atualizacao.xml` no servidor. **Esta configuração já foi feita no template.**

```xml
<updateservers>
    <server type="extension" priority="1" name="Servidor de Atualização do tpl_generico">
        http://apps.sobieskiproducoes.com.br/tpl_generico/atualizacao.xml
    </server>
</updateservers>
```

### 6.3. Estrutura de Arquivos no Servidor

O script de deploy (localizado em `.github/scripts/deploy.sh`) irá organizar os arquivos no servidor de FTP da seguinte forma:

```bash
/tpl_generico/
  ├── tpl_generico-<VERSAO_DA_TAG>.zip
  └── atualizacao.xml
```

### 6.4. Fluxo de Deploy Automático via GitHub Actions

1.  **Gatilho:** O fluxo é iniciado sempre que uma nova tag (ex: `v1.2.3`) é criada no GitHub.
2.  **Extração da Versão:** O workflow extrai a versão da tag.
3.  **Geração dos Pacotes:** O workflow gera o `atualizacao.xml` e o pacote `tpl_generico-<VERSAO_DA_TAG>.zip`.
4.  **Deploy via FTPS:** O script de deploy conecta ao servidor via **FTPS** (FTP sobre TLS) usando as credenciais armazenadas nos Secrets do GitHub (`FTP_URL`, `FTP_USUARIO`, `FTP_SENHA`).
5.  **Upload:** O script cria a pasta `/tpl_generico` se ela não existir e faz o upload dos dois arquivos gerados.
6.  **Validação:** O Joomla no site de produção irá detectar a nova versão e notificará o administrador para a atualização.
