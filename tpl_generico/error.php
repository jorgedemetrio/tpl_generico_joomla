<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\CMS\Document\ErrorDocument $this */

$app = Factory::getApplication();
$wa  = $this->getWebAssetManager();

// Load template's assets
HTMLHelper::_('bootstrap.framework');
$wa->usePreset('tpl_generico.preset');

// Get template params for the logo, with fallbacks
try {
	$params = Factory::getApplication()->getTemplate(true)->params;
	$logoFile = $params->get('logoFile');
	$siteTitle = $params->get('siteTitle');
} catch (\Exception $e) {
	$logoFile = '';
	$siteTitle = '';
}

$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8');
$logo = '';

if ($logoFile) {
    $logo = '<img src="' . Uri::root(false) . htmlspecialchars($logoFile, ENT_QUOTES) . '" alt="' . $sitename . '" loading="eager" />';
} elseif ($siteTitle) {
    $logo = '<span title="' . $sitename . '">' . htmlspecialchars($siteTitle, ENT_COMPAT, 'UTF-8') . '</span>';
} else {
    $logo = '<span title="' . $sitename . '">' . $sitename . '</span>';
}

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
$errorCode = $this->error->getCode();
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
    <jdoc:include type="head" />
</head>
<body class="site error-site">
    <header class="header container-header" role="banner">
        <div class="navbar-brand">
            <a class="brand-logo" href="<?php echo $this->baseurl; ?>/">
                <?php echo $logo; ?>
            </a>
        </div>
    </header>
    <main id="main-content">
        <div class="container">
            <div class="card">
                <div class="card-body">
                    <h1 class="page-header">
                        <span class="icon-warning text-danger" aria-hidden="true"></span>
                        <?php echo $errorCode; ?> - <?php echo htmlspecialchars($this->error->getMessage(), ENT_QUOTES, 'UTF-8'); ?>
                    </h1>
                    <p><strong><?php echo Text::_('JERROR_LAYOUT_ERROR_HAS_OCCURRED_WHILE_PROCESSING_YOUR_REQUEST'); ?></strong></p>
                    <p><?php echo Text::_('JERROR_LAYOUT_NOT_ABLE_TO_VISIT'); ?></p>
                    <ul>
                        <li><?php echo Text::_('JERROR_LAYOUT_AN_OUT_OF_DATE_BOOKMARK_FAVOURITE'); ?></li>
                        <li><?php echo Text::_('JERROR_LAYOUT_MIS_TYPED_ADDRESS'); ?></li>
                        <li><?php echo Text::_('JERROR_LAYOUT_SEARCH_ENGINE_OUT_OF_DATE_LISTING'); ?></li>
                        <li><?php echo Text::_('JERROR_LAYOUT_YOU_HAVE_NO_ACCESS_TO_THIS_PAGE'); ?></li>
                    </ul>
                    <p><a href="<?php echo $this->baseurl; ?>/" class="btn btn-primary"><?php echo Text::_('JERROR_LAYOUT_HOME_PAGE'); ?></a></p>
                </div>
            </div>
            <?php if ($this->debug) : ?>
                <div class="card mt-4">
                    <div class="card-body">
                        <?php echo $this->renderBacktrace(); ?>
                        <?php if ($this->error->getPrevious()) : ?>
                            <p><strong><?php echo Text::_('JERROR_LAYOUT_PREVIOUS_ERROR'); ?></strong></p>
                            <?php $loop = true;
                            $this->setError($this->_error->getPrevious());
                            while ($loop === true) : ?>
                                <p><?php echo htmlspecialchars($this->_error->getMessage(), ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php echo $this->renderBacktrace(); ?>
                                <?php $loop = $this->setError($this->_error->getPrevious());
                            endwhile;
                            $this->setError($this->error); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
