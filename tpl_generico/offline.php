<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;

/** @var Joomla\CMS\Document\HtmlDocument $this */

$app = Factory::getApplication();

// Load template's offline CSS
$wa = $this->getWebAssetManager();
HTMLHelper::_('bootstrap.framework');
$params = $app->getTemplate(true)->params;

// CSS Variable Generation
$cssVars = '';
$cssVars .= "--cor-primaria: {$params->get('primaryColor', '#1F4E79')};";
$cssVars .= "--cor-secundaria: {$params->get('secondaryColor', '#2E7D32')};";
$cssVars .= "--cor-cta: {$params->get('ctaColor', '#2F80ED')};";
$cssVars .= "--cor-texto: {$params->get('textColor', '#222222')};";
$cssVars .= "--cor-texto-secundario: {$params->get('textSecondaryColor', '#6B7280')};";
$cssVars .= "--cor-superficie-clara: {$params->get('surfaceLightColor', '#FFFFFF')};";
$cssVars .= "--cor-superficie-clara-topo: {$params->get('surfaceLightColorTopo', '#FFFFFF')};";
$cssVars .= "--cor-superficie-alt: {$params->get('surfaceAltColor', '#F5F7FA')};";
$cssVars .= "--cor-borda: {$params->get('borderColor', '#E5E7EB')};";
$cssVars .= "--espaco-interno-titulo-card: {$params->get('espacoInternoTituloCard', '1.5rem')};";
$cssVars .= "--margin-topo-titulo-card: {$params->get('margemTopoTituloCard', '10px')};";
$cssVars .= "--cor-footer: {$params->get('footerColor', '#0F172A')};";
$cssVars .= "--familia-fonte-primaria: {$params->get('fontFamilyPrimary', 'system-ui, sans-serif')};";
$cssVars .= "--tamanho-base-fonte: {$params->get('fontSizeBase', '1rem')};";
$cssVars .= "--peso-fonte-normal: {$params->get('fontWeightNormal', '400')};";
$cssVars .= "--peso-fonte-titulos: {$params->get('fontWeightHeadings', '700')};";
$cssVars .= "--raio-borda-global: {$params->get('borderRadius', '4')}px;";
$spacing = $params->get('verticalSpacing', 'M');
$spacingValue = '2rem';
if ($spacing === 'S') $spacingValue = '1rem';
if ($spacing === 'L') $spacingValue = '3rem';
$cssVars .= "--espacamento-vertical-global: {$spacingValue};";

// Enable assets
$wa->usePreset('tpl_generico.preset')->addInlineStyle(":root { $cssVars }");
$wa->useStyle('tpl_generico.offline');


// Logo file or site title param
$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8');
$logoWidth = $this->params->get('logoWidth', 150);
$logo = '';
try {
	$params = Factory::getApplication()->getTemplate(true)->params;
	if ($params->get('logoFile')) {
        $logo = '<img src="' . Uri::root(false) . htmlspecialchars($params->get('logoFile'), ENT_QUOTES) . '" alt="' . $sitename . '" title="' . $sitename . '" style="width: 500px; margin 0px auto;" loading="lazy" />';
	} else {
		$logo = '<span title="' . $sitename . '">' . htmlspecialchars($params->get('siteTitle', $sitename), ENT_COMPAT, 'UTF-8') . '</span>';
	}
} catch (\Exception $e) {
	$logo = '<span title="' . $sitename . '">' . $sitename . '</span>';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
    <title><?php echo htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8'); ?></title>
    <jdoc:include type="head" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="site offline">
    <div class="offline-card" style=" width: 500px; margin: 0 auto;">
        <div class="header">
            <h1><?php echo $logo; ?></h1>
        </div>
        <?php if ($app->get('display_offline_message', 1)) : ?>
            <p><?php echo $app->get('offline_message'); ?></p>
        <?php endif; ?>
        <div class="login">
            <jdoc:include type="message" />
            <form action="<?php echo Route::_('index.php', true); ?>" method="post" id="form-login">
                <fieldset>
                    <div class="mb-3">
                        <label for="username" class="form-label"><?php echo Text::_('JGLOBAL_USERNAME'); ?></label>
                        <input name="username" class="form-control" id="username" type="text">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?php echo Text::_('JGLOBAL_PASSWORD'); ?></label>
                        <input name="password" class="form-control" id="password" type="password">
                    </div>
                    <div class="mb-3">
                        <button type="submit" name="Submit" class="btn btn-primary w-100"><?php echo Text::_('JLOGIN'); ?></button>
                    </div>
                    <input type="hidden" name="option" value="com_users">
                    <input type="hidden" name="task" value="user.login">
                    <input type="hidden" name="return" value="<?php echo base64_encode(Uri::base()); ?>">
                    <?php echo HTMLHelper::_('form.token'); ?>
                </fieldset>
            </form>
        </div>
    </div>
</body>
</html>
