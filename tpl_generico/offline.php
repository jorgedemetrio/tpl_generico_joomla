<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\CMS\Document\HtmlDocument $this */

$app = Factory::getApplication();

// Load template's offline CSS
$wa = $app->getWebAssetManager();
$wa->useStyle('tpl_generico.offline');

// Logo file or site title param
$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8');
$logo = '';
try {
	$params = Factory::getApplication()->getTemplate(true)->params;
	if ($params->get('logoFile')) {
		$logo = '<img src="' . Uri::root(false) . htmlspecialchars($params->get('logoFile'), ENT_QUOTES) . '" alt="' . $sitename . '" loading="eager" />';
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
    <jdoc:include type="head" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="site offline">
    <div class="offline-card">
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
