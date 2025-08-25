# 🎨 Template Joomla 5 — tpl_generico

Este repositório contém o **template oficial Joomla 5** chamado **tpl_generico**, desenvolvido para ser um **tema base genérico**, **responsivo**, **acessível** e **otimizado para SEO**.  
Ele utiliza **Bootstrap 5.3.3** e **jQuery**.

---

## 📌 Objetivo

- Criar um **template limpo e moderno** com foco em **responsividade mobile-first**.  
- Oferecer **parametrizações no administrador** (cores, fontes, largura).  
- Seguir padrões de **SEO técnico** e **acessibilidade (WCAG AA)**.  
- Disponibilizar múltiplas **posições de módulos** para flexibilidade.  
- Garantir **manutenção simples e escalável** conforme padrões do projeto.

---

## Atualização

A URL onde é gerado a atualização : [`https://apps.sobieskiproducoes.com.br/tpl_generico/atualizacao.xml`](https://apps.sobieskiproducoes.com.br/tpl_generico/atualizacao.xml).

---

## 🧱 Estrutura do Template

```plaintext
/templates/tpl_generico/
├── css/                 # Arquivos CSS adicionais
├── js/                  # Scripts do template
├── html/                # Overrides de views/módulos Joomla
├── images/              # Imagens do tema
├── language/            # Arquivos de idioma (8 idiomas)
├── index.php            # Estrutura principal do template
└── templateDetails.xml  # Manifesto do template Joomla
```

---

## 📍 Posições de Módulo

- `topbar` — barra superior (idiomas, login, atalhos)  
- `below-top` — faixa de avisos/campanhas  
- `menu` — navegação principal (header fixo)  
- `search` — busca global  
- `banner` — herói/carrossel  
- `top-a`, `top-b` — blocos de destaque/CTAs  
- `main-top`, `main-bottom` — acima/abaixo do componente principal  
- `breadcrumbs` — trilha de navegação  
- `sidebar-left`, `sidebar-right` — colunas laterais  
- `bottom-a`, `bottom-b`, `bottom` — blocos finais de conteúdo  
- `footer` — rodapé escuro configurável  
- `debug` — saída técnica apenas para administradores  
- `message` — mensagens do Joomla  

**Regras de layout responsivo:**  
- Duas sidebars: conteúdo central ocupa 6 colunas.  
- Uma sidebar: conteúdo central ocupa 9 colunas.  
- Sem sidebar: conteúdo ocupa 12 colunas (100%).  

---

## ⚙️ Parametrização no Admin

- **Cores**: primária, secundária, CTA, textos, superfícies, rodapé.  
- **Fontes**: família tipográfica, tamanho base e pesos.  
- **Layout**: centralizado (boxed) ou expandido (full-width).  
- **Header**: sticky on/off, altura (compacta/normal), sombra.  
- **Menu mobile**: offcanvas ou collapse.  
- **Footer**: número de colunas (2/3/4) e ordem.  

> Todas as mudanças são aplicadas via **CSS custom properties** sem rebuild.

---

## 🔍 SEO e Acessibilidade

- **Um H1 por página** (na view principal).  
- **Breadcrumbs** publicados com microdados `BreadcrumbList`.  
- **Meta tags dinâmicas** (title, description, OG, Twitter).  
- **URLs amigáveis (SEF)** baseadas em alias.  
- **Schema.org** aplicado via overrides quando aplicável.  
- **Lazy loading** em imagens (`loading="lazy"`).  
- **Contraste mínimo WCAG AA**.  
- **Navegação por teclado** com foco visível.  

---

## 🚀 Como Começar

1. Instale o template no Joomla 5 via `templateDetails.xml`.  
2. Ative o template em **Extensões > Estilos de Template**.  
3. Configure cores, fontes e layout no painel de administração.  
4. Publique módulos nas posições definidas.  

---

## ⚡ Instalação Rápida com Script

Para uma instalação ou atualização rápida e automatizada, você pode usar o script `installTemplate.php`. Este script baixa a versão mais recente do template diretamente do repositório oficial e a instala no seu Joomla.

### Como Usar

1.  **Faça o upload do script**: Envie o arquivo `installTemplate.php` para o diretório raiz da sua instalação do Joomla (a mesma pasta onde se encontram os arquivos `configuration.php` e `index.php`).
2.  **Execute o script**: Acesse o script diretamente no seu navegador. Por exemplo: `https://seusite.com.br/installTemplate.php`.
3.  **Acompanhe o processo**: O script exibirá mensagens de status indicando o progresso do download e da instalação.
4.  **Remoção (Opcional, mas recomendado)**: Após a conclusão, por segurança, é uma boa prática remover o arquivo `installTemplate.php` do seu servidor.

### Pré-requisitos do Servidor

Para que o script funcione corretamente, seu servidor precisa ter as seguintes extensões PHP habilitadas:
-   `SimpleXML` (para ler o arquivo de atualização)
-   `cURL` ou `allow_url_fopen` habilitado (para baixar os arquivos)
-   `ZipArchive` (para descompactar o template)

---

## 🧭 Padrões de Desenvolvimento

- Seguir as diretrizes em `DEVELOPMENT_GUIDELINES.md`.  
- Usar **Web Asset Manager** para registrar CSS/JS.  
- Priorizar **segurança, SEO, acessibilidade e performance**.  

---

## ✅ Critérios de Aceite

- Header fixo funcional em desktop e mobile.  
- Layout ajusta corretamente com 0, 1 ou 2 sidebars.  
- Alterações do admin refletem no template imediatamente.  
- Breadcrumbs e metadados SEO renderizados corretamente.  
- Acessibilidade conforme WCAG AA.  
- Performance ≥ 90 (desktop) e ≥ 80 (mobile) no Lighthouse.  

---

## 📦 Manutenção

- Versionamento semântico (`vX.Y.Z`).  
- Registrar mudanças em **CHANGELOG.md**.  
- Atualizar documentação sempre que novos parâmetros ou funcionalidades forem adicionados.  
