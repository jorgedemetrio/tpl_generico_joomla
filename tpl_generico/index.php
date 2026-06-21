<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Helper\ModuleHelper;
require_once __DIR__ . '/helper.php';

/** @var Joomla\CMS\Document\HtmlDocument $this */
$app   = Factory::getApplication();
$input = $app->getInput();
$wa    = $this->getWebAssetManager();

// Viewport responsivo: sem isto o celular renderiza a pagina como desktop (~980px),
// o Bootstrap aplica .container { max-width: 720px } e o navegador "da zoom out" na
// pagina inteira — o container fica muito mais estreito que a tela do celular.
// (error.php/offline.php/component.php ja definem; o index.php estava sem.)
$this->setMetaData('viewport', 'width=device-width, initial-scale=1');

// Favicon
if ($faviconIco = $this->params->get('faviconIco')) {
    $this->addHeadLink(Uri::root(true) . '/' . htmlspecialchars($faviconIco), 'icon', 'rel', ['type' => 'image/vnd.microsoft.icon']);
}
if ($faviconPng32 = $this->params->get('faviconPng32')) {
    $this->addHeadLink(Uri::root(true) . '/' . htmlspecialchars($faviconPng32), 'icon', 'rel', ['type' => 'image/png', 'sizes' => '32x32']);
}
if ($faviconApple = $this->params->get('faviconApple')) {
    $this->addHeadLink(Uri::root(true) . '/' . htmlspecialchars($faviconApple), 'apple-touch-icon', 'rel', ['sizes' => '180x180']);
}

// CSS Variable Generation
$cssVars = TplGenericoHelper::buildCssVars($this->params);

// Enable assets
HTMLHelper::_('bootstrap.framework');
$wa->usePreset('tpl_generico.preset');
// addStyleDeclaration entra no buffer de inline styles do documento, garantindo
// que estas variaveis sejam renderizadas DEPOIS de bootstrap.css/template.css,
// vencendo qualquer :root anterior e fazendo as cores do admin de fato valerem.
$this->addStyleDeclaration(":root { $cssVars }");

// Fonte do Google (opcional): cole a URL completa do Google Fonts no admin.
// Faz preconnect (handshake antecipado) e carrega a folha de estilo.
$googleFontUrl = $this->params->get('googleFontUrl');
if ($googleFontUrl) {
    $preload = $this->getPreloadManager();
    $preload->preconnect('https://fonts.googleapis.com', ['crossorigin' => 'anonymous']);
    $preload->preconnect('https://fonts.gstatic.com', ['crossorigin' => 'anonymous']);
    // URL crua: o campo e type="url"/filter="url" (ja sanitizado) e o Joomla
    // escapa o href na renderizacao — escapar aqui causaria duplo-escape do "&".
    $wa->registerAndUseStyle('tpl_generico.googlefont', $googleFontUrl, [], ['crossorigin' => 'anonymous']);
}


$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8');
$option   = $input->getCmd('option', '');
$view     = $input->getCmd('view', '');
$layout   = $input->getCmd('layout', '');
$menu        = method_exists($app, 'getMenu') ? $app->getMenu() : null;
$activeMenu  = $menu ? $menu->getActive() : null;
$activeParams = ($activeMenu && method_exists($activeMenu, 'getParams')) ? $activeMenu->getParams() : null;
$pageclass   = $activeParams ? (string) $activeParams->get('pageclass_sfx', '') : '';

// SEO: canonical, fallback de meta description, theme-color, Open Graph e
// Twitter Cards (B1/B2/A1/A2/I2) + schemas globais Organization/WebSite na home
// (C1). Centralizado no helper para manter este index.php enxuto.
TplGenericoHelper::applyHeadSeo($this, $this->params, $app, $input);
TplGenericoHelper::injectGlobalJsonLd($this, $this->params, $app);

// Logo — esta acima da dobra: carrega com prioridade (eager + fetchpriority)
// e reserva espaco com width para reduzir layout shift (CLS).
$logo = TplGenericoHelper::buildLogo($this->params, $sitename, Uri::root(false));

// Sidebar and Grid Logic
$sidebarLeft  = $this->countModules('sidebar-left', true);
$sidebarRight = $this->countModules('sidebar-right', true);
$mainClass = TplGenericoHelper::mainColClass($sidebarLeft, $sidebarRight);
$containerClass = ($this->params->get('layoutWidth', 'boxed') === 'full-width') ? 'container-fluid' : 'container';

// Header settings
$stickyHeader = $this->params->get('stickyHeader', '1') === '1' ? 'sticky-top' : '';
$headerShadow = $this->params->get('headerShadow', '1') === '1' ? 'shadow-sm' : '';
$headerSeparator = $this->params->get('headerSeparator', '1') === '1' ? 'border-bottom' : '';
// Default alinhado ao templateDetails.xml (compact).
$headerHeightClass = 'header-' . $this->params->get('headerHeight', 'compact');

// Posicao da busca: 'inline' (dentro da navbar) ou 'below' (barra abaixo do header).
$searchPosition = $this->params->get('searchPosition', 'below');
$hasSearch      = $this->countModules('search', true);

// Botao de alternancia de tema (claro/escuro) no header — escolha persistida
// em localStorage pelo template.js.
$themeToggle = $this->params->get('themeToggle', '1') === '1';

// Barra de navegacao inferior (mobile): so renderiza com modulo na posicao.
$hasBottomNav = $this->countModules('bottom-nav', true);

// Posicoes do grid contadas 2x na montagem (no "if (a||b)" e em cada "if(a)"):
// cacheia uma vez para nao recontar (A2). Valor identico ao countModules direto.
$topA    = $this->countModules('top-a', true);
$topB    = $this->countModules('top-b', true);
$bottomA = $this->countModules('bottom-a', true);
$bottomB = $this->countModules('bottom-b', true);

// Aviso de cookies: banner discreto no rodape que NAO bloqueia a navegacao.
// O visitante aceita (ou e aceito automaticamente apos N segundos) e a escolha
// fica num cookie para nao repetir. Nao ha opcao de recusar — o site depende de
// cookies essenciais; a mensagem apenas informa isso de forma amigavel.
$cookieNotice  = $this->params->get('cookieNotice', '1') === '1';
$cookieTimeout = (int) $this->params->get('cookieNoticeTimeout', 20);
$cookieText    = trim((string) $this->params->get('cookieNoticeText', ''));

// Loader de navegacao: overlay central com spinner quando o usuario sai da
// pagina (clique em link interno, envio de formulario ou unload).
$pageLoader = $this->params->get('pageLoader', '1') === '1';
// Personalizacao do loader: cor do spinner e/ou imagem (GIF) que o substitui.
$pageLoaderColor = trim((string) $this->params->get('pageLoaderColor', ''));
$pageLoaderImage = trim((string) $this->params->get('pageLoaderImage', ''));
if ($pageLoaderImage !== '') {
    // O campo media pode retornar "images/x.gif#joomlaImage://..."; usa so o caminho.
    $pageLoaderImage = explode('#', $pageLoaderImage)[0];
}

// Newsletter: convite (modal) para o visitante se cadastrar. Vem DESLIGADO por
// padrao (ativavel no admin). So renderiza para quem NAO esta logado; o
// template.js cuida do "primeiro acesso" + tempo minimo no site (data-delay).
$user            = $app->getIdentity();
$isGuest         = $user ? ((int) $user->guest === 1) : true;
$newsletterModal = $isGuest && $this->params->get('newsletterModal', '0') === '1';
$newsletterDelay = (int) $this->params->get('newsletterModalDelay', 60);
if ($newsletterDelay < 0) {
    $newsletterDelay = 60;
}
$newsletterTitle = trim((string) $this->params->get('newsletterModalTitle', ''));
$newsletterText  = trim((string) $this->params->get('newsletterModalText', ''));
// Destino do cadastro: rota do Joomla (padrao = registo de utilizadores) ou
// URL completa para a pagina de newsletter do site. O e-mail vai como query.
$newsletterUrl   = trim((string) $this->params->get('newsletterModalUrl', 'index.php?option=com_users&view=registration'));
if ($newsletterUrl === '') {
    $newsletterUrl = 'index.php?option=com_users&view=registration';
}
if (!preg_match('#^https?://#i', $newsletterUrl)) {
    // Rota interna: deixa o Joomla gerar a URL (SEF). URL absoluta fica como esta.
    $newsletterUrl = Route::_($newsletterUrl);
}
$newsletterEmailParam = trim((string) $this->params->get('newsletterModalEmailParam', 'email'));
if ($newsletterEmailParam === '') {
    $newsletterEmailParam = 'email';
}

// Esquema de cores: light | dark | auto (auto segue o sistema do visitante).
$colorScheme = $this->params->get('colorScheme', 'light');
$htmlTheme   = in_array($colorScheme, ['light', 'dark'], true) ? $colorScheme : 'light';
// Define o tema antes da pintura (evita flash). Respeita uma escolha manual
// salva em localStorage quando o botao de tema esta ativo e segue o sistema no
// modo automatico. Roda no <head>, sincrono, antes do <body>.
if ($themeToggle || $colorScheme === 'auto') {
    $allowStored = $themeToggle ? 'true' : 'false';
    $this->addScriptDeclaration(
        "(function(){try{var K='" . TplGenericoHelper::THEME_STORAGE_KEY . "',r=document.documentElement,"
        . "allow=" . $allowStored . ",scheme='" . $colorScheme . "',"
        . "mql=window.matchMedia('(prefers-color-scheme: dark)'),s=null;"
        . "try{s=localStorage.getItem(K);}catch(e){}"
        . "function t(){if(allow&&(s==='light'||s==='dark'))return s;"
        . "if(scheme==='auto')return mql.matches?'dark':'light';return scheme;}"
        . "r.setAttribute('data-bs-theme',t());"
        . "if(scheme==='auto'){mql.addEventListener('change',function(){"
        . "if(!(allow&&(s==='light'||s==='dark')))r.setAttribute('data-bs-theme',mql.matches?'dark':'light');});}"
        . "}catch(e){}})();"
    );
}

// Integrations
$gtmId = $this->params->get('gtmId');
$fbPixelId = $this->params->get('fbPixelId');
// Handshake antecipado com os dominios de terceiros (reduz latencia do tracking).
if ($gtmId || $fbPixelId) {
    $preload = $this->getPreloadManager();
    if ($gtmId) {
        $preload->dnsPrefetch('https://www.googletagmanager.com');
        $preload->preconnect('https://www.googletagmanager.com');
    }
    if ($fbPixelId) {
        $preload->dnsPrefetch('https://connect.facebook.net');
        $preload->preconnect('https://connect.facebook.net');
    }
}
if ($gtmId) {
    $this->addScriptDeclaration("(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . htmlspecialchars($gtmId) . "');");
}
if ($fbPixelId) {
    $this->addScriptDeclaration("!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init', '" . htmlspecialchars($fbPixelId) . "');fbq('track', 'PageView');");
}

// Codigo livre do administrador (snippets completos: GTM, Pixel, verificacao
// de dominio, widgets de chat, etc.). Sao injetados crus — os campos usam
// filter="raw" no XML e so o gerenciador de templates (super admin) os edita.
$customHeadCode       = (string) $this->params->get('customHeadCode', '');
$customBodyTopCode    = (string) $this->params->get('customBodyTopCode', '');
$customBodyBottomCode = (string) $this->params->get('customBodyBottomCode', '');
if ($customHeadCode !== '') {
    // addCustomTag insere markup cru dentro do <head> (via jdoc:include head).
    $this->addCustomTag($customHeadCode);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-bs-theme="<?php echo $htmlTheme; ?>" data-theme-key="<?php echo TplGenericoHelper::THEME_STORAGE_KEY; ?>">
<head>
    <jdoc:include type="head" />
</head>
<body class="site <?php echo $option . ' view-' . $view . ($layout ? ' layout-' . $layout : '') . ($pageclass ? ' ' . $pageclass : '') . ($hasBottomNav ? ' has-bottom-nav' : ''); ?>">
    <a class="visually-hidden-focusable skip-link" href="#main-content"><?php echo Text::_('TPL_GENERICO_SKIP_TO_CONTENT'); ?></a>
    <?php if ($gtmId) : ?><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo htmlspecialchars($gtmId); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript><?php endif; ?>
    <?php if ($fbPixelId) : ?><noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo htmlspecialchars($fbPixelId); ?>&ev=PageView&noscript=1" /></noscript><?php endif; ?>
    <?php // Codigo livre logo apos a abertura do <body> (ex.: <noscript> do GTM).
    echo $customBodyTopCode; ?>

    <header id="header" class="header <?php echo $stickyHeader . ' ' . $headerShadow . ' ' . $headerSeparator . ' ' . $headerHeightClass; ?>" role="banner">
        <?php if ($this->countModules('topbar', true)) : ?>
        <div id="topbar" class="bg-light"><div class="<?php echo $containerClass; ?>"><jdoc:include type="modules" name="topbar" style="none" /></div></div>
        <?php endif; ?>
        <?php if ($this->countModules('below-top', true)) : ?>
        <div id="below-top"><div class="<?php echo $containerClass; ?>"><jdoc:include type="modules" name="below-top" style="none" /></div></div>
        <?php endif; ?>
        <nav class="navbar navbar-expand-lg" aria-label="<?php echo Text::_('TPL_GENERICO_MAIN_NAV_LABEL'); ?>">
            <div class="<?php echo $containerClass; ?>">
                <a class="navbar-brand" href="<?php echo $this->baseurl; ?>/"><?php echo $logo; ?></a>

                <div class="d-flex align-items-center ms-auto">
                    <?php if ($themeToggle) : ?>
                        <button type="button" id="themeToggle" class="theme-toggle me-2" aria-pressed="false" aria-label="<?php echo Text::_('TPL_GENERICO_THEME_TOGGLE_ARIA'); ?>" title="<?php echo Text::_('TPL_GENERICO_THEME_TOGGLE_ARIA'); ?>">
                            <i class="fas fa-moon" aria-hidden="true"></i>
                        </button>
                    <?php endif; ?>
                    <?php if ($this->countModules('mobile-menu', true)) : ?>
                        <button class="navbar-toggler d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenuArea" aria-controls="mobileMenuArea" aria-label="<?php echo Text::_('TPL_GENERICO_MOBILE_MENU_TOGGLE'); ?>">
                            <i class="fas fa-bars" aria-hidden="true"></i>
                        </button>
                    <?php endif; ?>

                    <?php if ($this->countModules('menu', true)) :
                        $mobileMenuBehavior = $this->params->get('mobileMenuBehavior', 'offcanvas');
                        // Id unico por comportamento: evita "duplicate id" (o collapse e o
                        // offcanvas usam a mesma posicao 'menu', mas so um renderiza por vez).
                        $menuTargetId       = $mobileMenuBehavior === 'collapse' ? 'mobileMenuCollapse' : 'mobileMenuOffcanvas';
                    ?>
                        <button class="navbar-toggler" type="button" data-bs-toggle="<?php echo $mobileMenuBehavior; ?>" data-bs-target="#<?php echo $menuTargetId; ?>" aria-controls="<?php echo $menuTargetId; ?>" aria-expanded="false" aria-label="<?php echo Text::_('TPL_GENERICO_MAIN_NAV_TOGGLE'); ?>">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($this->countModules('menu', true)) : ?>
                    <?php if ($mobileMenuBehavior === 'collapse') : ?>
                        <div class="collapse navbar-collapse" id="<?php echo $menuTargetId; ?>"><jdoc:include type="modules" name="menu" style="none" /></div>
                    <?php else : ?>
                        <div class="offcanvas offcanvas-end" tabindex="-1" id="<?php echo $menuTargetId; ?>" aria-labelledby="mobileMenuLabel">
                            <div class="offcanvas-header"><h5 class="offcanvas-title" id="mobileMenuLabel"><?php echo Text::_('TPL_GENERICO_MENU_TITLE'); ?></h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button></div>
                            <div class="offcanvas-body"><jdoc:include type="modules" name="menu" style="none" /></div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($hasSearch && $searchPosition === 'inline') : ?><div id="search-header"><jdoc:include type="modules" name="search" style="none" /></div><?php endif; ?>
            </div>
        </nav>
        <?php if ($this->countModules('mobile-menu', true)) : ?>
            <div class="offcanvas offcanvas-start w-100 h-100 border-0 d-lg-none" tabindex="-1" id="mobileMenuArea" aria-labelledby="mobileMenuAreaLabel">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title" id="mobileMenuAreaLabel"><?php echo Text::_('TPL_GENERICO_MOBILE_MENU_TITLE'); ?></h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
                </div>
                <div class="offcanvas-body overflow-auto">
                    <jdoc:include type="modules" name="mobile-menu" style="none" />
                </div>
            </div>
        <?php endif; ?>
        <?php if ($hasSearch && $searchPosition === 'below') : ?>
        <div id="search-below" class="border-top"><div class="<?php echo $containerClass; ?> py-2"><jdoc:include type="modules" name="search" style="none" /></div></div>
        <?php endif; ?>
    </header>

    <?php // O landmark "banner" ja e o <header>; aqui usamos aria-label para nao
    // duplicar o role banner (deve ser unico na pagina). ?>
    <?php if ($this->countModules('banner', true)) : ?><section id="banner" aria-label="<?php echo Text::_('TPL_GENERICO_BANNER_LABEL'); ?>"><jdoc:include type="modules" name="banner" style="none" /></section><?php endif; ?>



    <main id="main-content" role="main" tabindex="-1">
        <div class="<?php echo $containerClass; ?>">
            <?php if ($this->countModules('breadcrumbs', true)) : ?>
            <div class="row"><div class="col-12"><nav aria-label="<?php echo Text::_('TPL_GENERICO_BREADCRUMB_LABEL'); ?>"><jdoc:include type="modules" name="breadcrumbs" style="none" /></nav></div></div>
            <?php endif; ?>
            <?php if ($topA || $topB) : ?>
            <div class="row">
                <?php if ($topA) : ?><div class="col-12 col-md-6"><jdoc:include type="modules" name="top-a" style="card" /></div><?php endif; ?>
                <?php if ($topB) : ?><div class="col-12 col-md-6"><jdoc:include type="modules" name="top-b" style="card" /></div><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="row">
                <?php if ($sidebarLeft) : ?>
                <aside id="sidebar-left" class="col-lg-3 d-none d-lg-block" aria-label="<?php echo Text::_('TPL_GENERICO_SIDEBAR_LEFT_TITLE'); ?>">
                    <div class="sidebar-content"><jdoc:include type="modules" name="sidebar-left" style="card" /></div>
                </aside>
                <?php endif; ?>
                <div id="component-area" class="<?php echo $mainClass; ?>">
                    <div id="system-message-container"><jdoc:include type="message" /></div>
                    <?php if ($this->countModules('main-top', true)) : ?><jdoc:include type="modules" name="main-top" style="card" /><?php endif; ?>
                    <jdoc:include type="component" />
                    <?php if ($this->countModules('main-bottom', true)) : ?><jdoc:include type="modules" name="main-bottom" style="card" /><?php endif; ?>
                </div>
                <?php if ($sidebarRight) : ?>
                <aside id="sidebar-right" class="col-lg-3 d-none d-lg-block" aria-label="<?php echo Text::_('TPL_GENERICO_SIDEBAR_RIGHT_TITLE'); ?>">
                    <div class="sidebar-content"><jdoc:include type="modules" name="sidebar-right" style="card" /></div>
                </aside>
                <?php endif; ?>
            </div>
            <?php if ($bottomA || $bottomB) : ?>
            <div class="row">
                <?php if ($bottomA) : ?><div class="col-12 col-md-6"><jdoc:include type="modules" name="bottom-a" style="card" /></div><?php endif; ?>
                <?php if ($bottomB) : ?><div class="col-12 col-md-6"><jdoc:include type="modules" name="bottom-b" style="card" /></div><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($this->countModules('bottom', true)) : ?>
            <div class="row"><div class="col-12"><jdoc:include type="modules" name="bottom" style="none" /></div></div>
            <?php endif; ?>
        </div>
    </main>

    <?php if ($this->countModules('footer', true)) : ?>
    <footer id="footer" class="footer" role="contentinfo">
        <div class="<?php echo $containerClass; ?>">
            <div class="row">
            <?php
                $footerModules = ModuleHelper::getModules('footer');
                $footerColumns = (int) $this->params->get('footerColumns', 4);
                // No celular cada item ocupa a linha inteira (col-12); so a partir do
                // tablet (md, >=768px) divide em 2/linha e abre nas N colunas no desktop
                // (lg). Antes usava col-sm-6 (>=576px), que ja espremia 2 itens em
                // celulares grandes/paisagem — deixando o rodape apertado.
                $colClass = TplGenericoHelper::footerColClass($footerColumns);
                foreach ($footerModules as $module) {
                    echo '<div class="' . $colClass . '">';
                    echo ModuleHelper::renderModule($module);
                    echo '</div>';
                }
            ?>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    <?php if ($hasBottomNav) : ?>
    <nav id="bottom-nav" class="bottom-nav d-md-none" aria-label="<?php echo Text::_('TPL_GENERICO_BOTTOM_NAV_LABEL'); ?>">
        <jdoc:include type="modules" name="bottom-nav" style="none" />
    </nav>
    <?php endif; ?>

    <?php if ($cookieNotice) : ?>
    <section id="cookieNotice" class="cookie-notice" aria-label="<?php echo Text::_('TPL_GENERICO_COOKIE_NOTICE_REGION'); ?>" data-timeout="<?php echo $cookieTimeout; ?>" hidden>
        <div class="cookie-notice-inner">
            <p class="cookie-notice-text"><?php echo $cookieText !== '' ? $cookieText : Text::_('TPL_GENERICO_COOKIE_NOTICE_TEXT'); ?></p>
            <button type="button" id="cookieAccept" class="btn btn-primary btn-sm cookie-notice-accept"><?php echo Text::_('TPL_GENERICO_COOKIE_ACCEPT'); ?><span class="cookie-notice-countdown" aria-hidden="true"></span></button>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($newsletterModal) : ?>
    <div id="newsletterModal" class="newsletter-modal" role="dialog" aria-modal="true" aria-labelledby="newsletterModalTitle" aria-describedby="newsletterModalText" data-delay="<?php echo $newsletterDelay; ?>" hidden>
        <div class="newsletter-modal-dialog">
            <button type="button" class="newsletter-modal-close" data-newsletter-dismiss aria-label="<?php echo Text::_('JCLOSE'); ?>">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
            <h2 id="newsletterModalTitle" class="newsletter-modal-title"><?php echo $newsletterTitle !== '' ? htmlspecialchars($newsletterTitle, ENT_QUOTES, 'UTF-8') : Text::_('TPL_GENERICO_NEWSLETTER_TITLE'); ?></h2>
            <div id="newsletterModalText" class="newsletter-modal-text"><?php echo $newsletterText !== '' ? $newsletterText : Text::_('TPL_GENERICO_NEWSLETTER_TEXT'); ?></div>
            <form class="newsletter-modal-form" action="<?php echo htmlspecialchars($newsletterUrl, ENT_QUOTES); ?>" method="get" data-email-param="<?php echo htmlspecialchars($newsletterEmailParam, ENT_QUOTES); ?>" novalidate>
                <label class="visually-hidden" for="newsletterModalEmail"><?php echo Text::_('TPL_GENERICO_NEWSLETTER_EMAIL_LABEL'); ?></label>
                <input type="email" id="newsletterModalEmail" name="<?php echo htmlspecialchars($newsletterEmailParam, ENT_QUOTES); ?>" class="form-control" required autocomplete="email" placeholder="<?php echo Text::_('TPL_GENERICO_NEWSLETTER_EMAIL_PLACEHOLDER'); ?>" />
                <p class="newsletter-modal-error" id="newsletterModalError" role="alert" hidden><?php echo Text::_('TPL_GENERICO_NEWSLETTER_INVALID_EMAIL'); ?></p>
                <div class="newsletter-modal-actions">
                    <button type="submit" class="btn btn-primary newsletter-modal-submit"><?php echo Text::_('TPL_GENERICO_NEWSLETTER_SUBMIT'); ?></button>
                    <button type="button" class="btn btn-link newsletter-modal-decline" data-newsletter-dismiss><?php echo Text::_('TPL_GENERICO_NEWSLETTER_DECLINE'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <button id="backToTop" class="back-to-top" type="button" aria-label="<?php echo Text::_('TPL_GENERICO_BACK_TO_TOP'); ?>" title="<?php echo Text::_('TPL_GENERICO_BACK_TO_TOP'); ?>">
        <i class="fas fa-chevron-up" aria-hidden="true"></i>
    </button>

    <?php if ($pageLoader) : ?>
    <div id="pageLoader" class="page-loader" role="status" aria-live="polite" hidden>
        <div class="page-loader-box">
            <?php if ($pageLoaderImage !== '') : ?>
            <img class="page-loader-img" src="<?php echo Uri::root(false) . htmlspecialchars($pageLoaderImage, ENT_QUOTES); ?>" alt="" aria-hidden="true" />
            <?php else : ?>
            <div class="spinner-border"<?php echo $pageLoaderColor !== '' ? ' style="color: ' . htmlspecialchars($pageLoaderColor, ENT_QUOTES) . '"' : ''; ?> aria-hidden="true"></div>
            <?php endif; ?>
            <span class="visually-hidden"><?php echo Text::_('TPL_GENERICO_LOADING'); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <jdoc:include type="modules" name="debug" style="none" />
    <?php // Codigo livre antes do fechamento do </body> (ex.: scripts de rodape, chat).
    echo $customBodyBottomCode; ?>
</body>
</html>
