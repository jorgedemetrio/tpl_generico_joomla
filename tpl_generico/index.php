<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;

/** @var Joomla\CMS\Document\HtmlDocument $this */

$app   = Factory::getApplication();
$input = $app->getInput();
$wa    = $this->getWebAssetManager();

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
$cssVars = '';
$cssVars .= "--cor-primaria: {$this->params->get('primaryColor', '#1F4E79')};";
$cssVars .= "--cor-secundaria: {$this->params->get('secondaryColor', '#2E7D32')};";
$cssVars .= "--cor-cta: {$this->params->get('ctaColor', '#2F80ED')};";
$cssVars .= "--cor-texto: {$this->params->get('textColor', '#222222')};";
$cssVars .= "--cor-texto-secundario: {$this->params->get('textSecondaryColor', '#6B7280')};";
$cssVars .= "--cor-superficie-clara: {$this->params->get('surfaceLightColor', '#FFFFFF')};";
$cssVars .= "--cor-superficie-clara-topo: {$this->params->get('surfaceLightColorTopo', '#FFFFFF')};";
$cssVars .= "--cor-superficie-alt: {$this->params->get('surfaceAltColor', '#F5F7FA')};";
$cssVars .= "--cor-borda: {$this->params->get('borderColor', '#E5E7EB')};";
$cssVars .= "--cor-footer: {$this->params->get('footerColor', '#0F172A')};";
$cssVars .= "--familia-fonte-primaria: {$this->params->get('fontFamilyPrimary', 'system-ui, sans-serif')};";
$cssVars .= "--tamanho-base-fonte: {$this->params->get('fontSizeBase', '1rem')};";
$cssVars .= "--peso-fonte-normal: {$this->params->get('fontWeightNormal', '400')};";
$cssVars .= "--peso-fonte-titulos: {$this->params->get('fontWeightHeadings', '700')};";
$cssVars .= "--raio-borda-global: {$this->params->get('borderRadius', '4')}px;";
$spacing = $this->params->get('verticalSpacing', 'M');
$spacingValue = '2rem';
if ($spacing === 'S') $spacingValue = '1rem';
if ($spacing === 'L') $spacingValue = '3rem';
$cssVars .= "--espacamento-vertical-global: {$spacingValue};";

// Enable assets
//HTMLHelper::_('bootstrap.framework');
$wa->usePreset('tpl_generico.preset')->addInlineStyle(":root { $cssVars }");


$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8');
$option   = $input->getCmd('option', '');
$view     = $input->getCmd('view', '');
$layout   = $input->getCmd('layout', '');
$pageclass = $app->getMenu()->getActive() ? $app->getMenu()->getActive()->getParams()->get('pageclass_sfx', '') : '';

// Logo
$logoWidth = $this->params->get('logoWidth', 150);
$logo = '';
if ($this->params->get('logoFile')) {
    $logo = '<img src="' . Uri::root(false) . htmlspecialchars($this->params->get('logoFile'), ENT_QUOTES) . '" alt="' . $sitename . '" title="' . $sitename . '" style="width: ' . (int) $logoWidth . 'px;" loading="lazy" />';
} else {
    $logo = '<span class="site-title" title="' . $sitename . '">' . htmlspecialchars($this->params->get('siteTitle', $sitename), ENT_COMPAT, 'UTF-8') . '</span>';
}

// Sidebar and Grid Logic
$sidebarLeft  = $this->countModules('sidebar-left', true);
$sidebarRight = $this->countModules('sidebar-right', true);
$mainClass = 'col-12';
if ($sidebarLeft && $sidebarRight) $mainClass = 'col-lg-6';
elseif ($sidebarLeft || $sidebarRight) $mainClass = 'col-lg-9';
$containerClass = ($this->params->get('layoutWidth', 'boxed') === 'full-width') ? 'container-fluid' : 'container';

// Header settings
$stickyHeader = $this->params->get('stickyHeader', '1') === '1' ? 'sticky-top' : '';
$headerShadow = $this->params->get('headerShadow', '1') === '1' ? 'shadow-sm' : '';
$headerSeparator = $this->params->get('headerSeparator', '1') === '1' ? 'border-bottom' : '';
$headerHeightClass = 'header-' . $this->params->get('headerHeight', 'normal');

// Integrations
$gtmId = $this->params->get('gtmId');
$fbPixelId = $this->params->get('fbPixelId');
if ($gtmId) {
    $this->addScriptDeclaration("(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . htmlspecialchars($gtmId) . "');");
}
if ($fbPixelId) {
    $this->addScriptDeclaration("!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init', '" . htmlspecialchars($fbPixelId) . "');fbq('track', 'PageView');");
}
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <jdoc:include type="head" />
</head>
<body class="site <?php echo $option . ' view-' . $view . ($layout ? ' layout-' . $layout : '') . ($pageclass ? ' ' . $pageclass : ''); ?>">
    <?php if ($gtmId) : ?><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo htmlspecialchars($gtmId); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript><?php endif; ?>
    <?php if ($fbPixelId) : ?><noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo htmlspecialchars($fbPixelId); ?>&ev=PageView&noscript=1" /></noscript><?php endif; ?>

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
                <?php if ($this->countModules('menu', true)) :
                    $mobileMenuBehavior = $this->params->get('mobileMenuBehavior', 'offcanvas');
                ?><button class="navbar-toggler" type="button" data-bs-toggle="<?php echo $mobileMenuBehavior; ?>" data-bs-target="#mobileMenu" aria-controls="mobileMenu" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
                    <?php if ($mobileMenuBehavior === 'collapse') : ?>
                        <div class="collapse navbar-collapse" id="mobileMenu"><jdoc:include type="modules" name="menu" style="none" /></div>
                    <?php else : ?>
                        <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
                            <div class="offcanvas-header"><h5 class="offcanvas-title" id="mobileMenuLabel"><?php echo Text::_('TPL_GENERICO_MENU_TITLE'); ?></h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
                            <div class="offcanvas-body"><jdoc:include type="modules" name="menu" style="none" /></div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($this->countModules('search', true)) : ?><div id="search-header"><jdoc:include type="modules" name="search" style="none" /></div><?php endif; ?>
            </div>
        </nav>
    </header>

    <?php if ($this->countModules('banner', true)) : ?><section id="banner" role="banner"><jdoc:include type="modules" name="banner" style="none" /></section><?php endif; ?>

    <div id="system-message-container"><jdoc:include type="message" /></div>

    <main id="main-content" role="main">
        <div class="<?php echo $containerClass; ?>">
            <?php if ($this->countModules('breadcrumbs', true)) : ?>
            <div class="row"><div class="col-12"><nav aria-label="breadcrumb"><jdoc:include type="modules" name="breadcrumbs" style="none" /></nav></div></div>
            <?php endif; ?>
            <?php if ($this->countModules('top-a', true) || $this->countModules('top-b', true)) : ?>
            <div class="row">
                <?php if ($this->countModules('top-a', true)) : ?><div class="col-md-6"><jdoc:include type="modules" name="top-a" style="card" /></div><?php endif; ?>
                <?php if ($this->countModules('top-b', true)) : ?><div class="col-md-6"><jdoc:include type="modules" name="top-b" style="card" /></div><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="row">
                <?php if ($sidebarLeft) : ?><aside id="sidebar-left" class="col-lg-3" role="complementary"><jdoc:include type="modules" name="sidebar-left" style="card" /></aside><?php endif; ?>
                <div id="component-area" class="<?php echo $mainClass; ?>">
                    <?php if ($this->countModules('main-top', true)) : ?><jdoc:include type="modules" name="main-top" style="card" /><?php endif; ?>
                    <jdoc:include type="component" />
                    <?php if ($this->countModules('main-bottom', true)) : ?><jdoc:include type="modules" name="main-bottom" style="card" /><?php endif; ?>
                </div>
                <?php if ($sidebarRight) : ?><aside id="sidebar-right" class="col-lg-3" role="complementary"><jdoc:include type="modules" name="sidebar-right" style="card" /></aside><?php endif; ?>
            </div>
            <?php if ($this->countModules('bottom-a', true) || $this->countModules('bottom-b', true)) : ?>
            <div class="row">
                <?php if ($this->countModules('bottom-a', true)) : ?><div class="col-md-6"><jdoc:include type="modules" name="bottom-a" style="card" /></div><?php endif; ?>
                <?php if ($this->countModules('bottom-b', true)) : ?><div class="col-md-6"><jdoc:include type="modules" name="bottom-b" style="card" /></div><?php endif; ?>
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
                $colClass = 'col-md-3';
                if ($footerColumns === 3) $colClass = 'col-md-4';
                elseif ($footerColumns === 2) $colClass = 'col-md-6';
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
    <jdoc:include type="modules" name="debug" style="none" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
