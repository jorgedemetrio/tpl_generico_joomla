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
    -   `template.js`: O principal arquivo de scripts. Funcionalidades customizadas em JavaScript/jQuery devem ser adicionadas aqui.

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

O template disponibiliza as seguintes bibliotecas JavaScript, carregadas via CDN no `index.php`:
-   **Bootstrap 5.3.3**: O framework completo de componentes e plugins.
-   **JQuery 3.7.1**: Embora o Joomla 5 não o inclua por padrão, este template o carrega para facilitar a manipulação do DOM e garantir compatibilidade com scripts que dependam dele.

**Arquivo de Scripts Customizados:**
-   As funcionalidades específicas do template devem ser adicionadas em `js/template.js`.
-   Atualmente, este arquivo contém um script em JavaScript puro (vanilla JS) para ajustar o espaçamento do header fixo.
-   Você pode escrever código novo usando tanto JQuery (`$(document).ready(...)`) quanto JavaScript puro, conforme a necessidade.

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
