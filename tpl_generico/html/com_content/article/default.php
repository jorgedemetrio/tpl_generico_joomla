<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Joomla\Component\Content\Site\Helper\RouteHelper;

// JSON-LD Data Structure
try {
    $app = Factory::getApplication();
    $document = $app->getDocument();
    $template = $app->getTemplate(true);

    // Determine Article Type
    $publishDate = Factory::getDate($this->item->publish_up)->format('Y-m-d');
    $today = Factory::getDate('now', $app->get('offset'))->format('Y-m-d');
    $articleType = ($publishDate === $today) ? 'NewsArticle' : 'Article';

    // Publisher Info
    $siteName = $app->get('sitename');
    $logoFile = $template->params->get('logoFile');
    $logoUrl = $logoFile ? Uri::base() . htmlspecialchars($logoFile) : '';

    // Image Info
    $images = json_decode($this->item->images);
    $imageUrl = !empty($images->image_fulltext) ? Uri::base() . htmlspecialchars($images->image_fulltext) : '';

    // @id canonico: so o caminho, sem query string (ordenacao/paginacao/print
    // gerariam @ids divergentes para o mesmo artigo).
    $canonicalId = Uri::getInstance()->toString(['scheme', 'host', 'port', 'path']);

    $jsonLdData = [
        '@context'      => 'https://schema.org',
        '@type'         => $articleType,
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id'   => $canonicalId,
        ],
        'headline'      => $this->item->title,
        'datePublished' => HTMLHelper::_('date', $this->item->publish_up, 'c'),
        'dateModified'  => HTMLHelper::_('date', $this->item->modified, 'c'),
        'publisher'     => [
            '@type' => 'Organization',
            'name'  => $siteName,
        ],
    ];

    // author so quando ha valor — "name": null invalida o JSON-LD.
    $authorName = isset($this->item->author) ? trim((string) $this->item->author) : '';
    if ($authorName !== '') {
        $jsonLdData['author'] = [
            '@type' => 'Person',
            'name'  => $authorName,
        ];
    }

    // description a partir da meta description do artigo, quando definida.
    $metaDesc = isset($this->item->metadesc) ? trim((string) $this->item->metadesc) : '';
    if ($metaDesc !== '') {
        $jsonLdData['description'] = $metaDesc;
    }

    if ($imageUrl) {
        $jsonLdData['image'] = [
            '@type' => 'ImageObject',
            'url' => $imageUrl,
        ];
    }

    if ($logoUrl) {
        $jsonLdData['publisher']['logo'] = [
            '@type' => 'ImageObject',
            'url' => $logoUrl,
        ];
    }

    // JSON_HEX_TAG protege contra fechamento prematuro de </script> via titulo/autor.
    $jsonLd = json_encode($jsonLdData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $scriptTag = '<script type="application/ld+json">' . PHP_EOL . $jsonLd . PHP_EOL . '</script>';
    $document->addCustomTag($scriptTag);

} catch (Exception $e) {
    // Never break the site
}

// D1 — hreflang/alternate no head para sites multilingues. Reaproveita as
// associacoes de idioma do artigo (o mesmo conteudo em outras linguas). Tudo
// sob guardas: sem associacoes/Multilingue, nada e emitido. Nunca quebra a pagina.
try {
    if (Associations::isEnabled()
        && class_exists('Joomla\\Component\\Content\\Site\\Helper\\AssociationHelper')) {
        $assocApp = Factory::getApplication();
        $assocDoc = $assocApp->getDocument();
        $assocCat = isset($this->item->catid) ? (int) $this->item->catid : 0;
        $assoc    = \Joomla\Component\Content\Site\Helper\AssociationHelper::getAssociations((int) $this->item->id, $assocCat);
        $assocRoot      = rtrim(Uri::root(), '/');
        $assocCanonical = Uri::getInstance()->toString(['scheme', 'host', 'port', 'path']);

        if (is_array($assoc) && count($assoc) > 0) {
            foreach ($assoc as $assocTag => $assocEntry) {
                // O valor pode vir como rota (string) ou array com 'link'/'url'.
                $assocLink = is_array($assocEntry)
                    ? (string) ($assocEntry['link'] ?? ($assocEntry['url'] ?? ''))
                    : (string) $assocEntry;
                if ($assocTag === '' || $assocLink === '') {
                    continue;
                }
                $assocAbs = preg_match('#^https?://#i', $assocLink)
                    ? $assocLink
                    : $assocRoot . '/' . ltrim($assocLink, '/');
                $assocDoc->addHeadLink($assocAbs, 'alternate', 'rel', ['hreflang' => $assocTag]);
            }

            // hreflang auto-referente do idioma atual (recomendado) + x-default.
            $selfTag = isset($this->item->language) ? (string) $this->item->language : '';
            if ($selfTag !== '' && $selfTag !== '*') {
                $assocDoc->addHeadLink($assocCanonical, 'alternate', 'rel', ['hreflang' => $selfTag]);
            }
            $assocDoc->addHeadLink($assocCanonical, 'alternate', 'rel', ['hreflang' => 'x-default']);
        }
    }
} catch (\Throwable $e) {
    // Nunca quebrar o artigo por causa do hreflang.
}

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */
// Create shortcuts to some parameters.
$params  = $this->item->params;
$canEdit = $params->get('access-edit');
$user    = $this->getCurrentUser();
$info    = $params->get('info_block_position', 0);
$htag    = $this->params->get('show_page_heading') ? 'h2' : 'h1';

// Check if associations are implemented. If they are, define the parameter.
$assocParam        = (Associations::isEnabled() && $params->get('show_associations'));
$currentDate       = Factory::getDate()->format('Y-m-d H:i:s');
$isNotPublishedYet = $this->item->publish_up > $currentDate;
$isExpired         = !is_null($this->item->publish_down) && $this->item->publish_down < $currentDate;


$app = Factory::getApplication();
$document = $app->getDocument();
if($this->item && $this->item->metakey){
    $document->setMetadata(htmlspecialchars('keywords', ENT_COMPAT, 'UTF-8'), htmlspecialchars($this->item->metakey, ENT_COMPAT, 'UTF-8'));
}


// Dispara o evento ViewContent do Facebook Pixel quando ele estiver presente.
// Vanilla JS (o template nao carrega mais jQuery) e com guarda: se o Pixel nao
// estiver configurado, `fbq` nao existe e o script simplesmente nao faz nada.
$document->addScriptDeclaration("
	document.addEventListener('DOMContentLoaded', function () {
		if (typeof fbq === 'function') {
			fbq('track', 'ViewContent');
		}
	});
");

?>
<div class="com-content-article item-page<?php echo $this->pageclass_sfx; ?>">
    <meta itemprop="inLanguage" content="<?php echo ($this->item->language === '*') ? Factory::getApplication()->get('language') : $this->item->language; ?>">
    <?php if ($this->params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>
    </div>
    <?php endif;
    if (!empty($this->item->pagination) && !$this->item->paginationposition && $this->item->paginationrelative) {
        echo $this->item->pagination;
    }
    ?>

    <?php $useDefList = $params->get('show_modify_date') || $params->get('show_publish_date') || $params->get('show_create_date')
    || $params->get('show_hits') || $params->get('show_category') || $params->get('show_parent_category') || $params->get('show_author') || $assocParam; ?>

    <?php if ($params->get('show_title')) : ?>
    <div class="page-header">
        <<?php echo $htag; ?>>
            <?php echo $this->escape($this->item->title); ?>
        </<?php echo $htag; ?>>
        <?php if ($this->item->state == ContentComponent::CONDITION_UNPUBLISHED) : ?>
            <span class="badge bg-warning text-light"><?php echo Text::_('JUNPUBLISHED'); ?></span>
        <?php endif; ?>
        <?php if ($isNotPublishedYet) : ?>
            <span class="badge bg-warning text-light"><?php echo Text::_('JNOTPUBLISHEDYET'); ?></span>
        <?php endif; ?>
        <?php if ($isExpired) : ?>
            <span class="badge bg-warning text-light"><?php echo Text::_('JEXPIRED'); ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($canEdit) : ?>
        <?php echo LayoutHelper::render('joomla.content.icons', ['params' => $params, 'item' => $this->item]); ?>
    <?php endif; ?>

    <?php // Content is generated by content plugin event "onContentAfterTitle" ?>
    <?php echo $this->item->event->afterDisplayTitle; ?>

    <?php if ($useDefList && ($info == 0 || $info == 2)) : ?>
        <?php echo LayoutHelper::render('joomla.content.info_block', ['item' => $this->item, 'params' => $params, 'position' => 'above']); ?>
    <?php endif; ?>

    <?php if ($info == 0 && $params->get('show_tags', 1) && !empty($this->item->tags->itemTags)) : ?>
        <?php $this->item->tagLayout = new FileLayout('joomla.content.tags'); ?>

        <?php echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
    <?php endif; ?>

    <?php // Content is generated by content plugin event "onContentBeforeDisplay" ?>
    <?php echo $this->item->event->beforeDisplayContent; ?>

    <?php if ((int) $params->get('urls_position', 0) === 0) : ?>
        <?php echo $this->loadTemplate('links'); ?>
    <?php endif; ?>
    <?php if ($params->get('access-view')) : ?>
        <?php echo LayoutHelper::render('joomla.content.full_image', $this->item); ?>
        <?php
        if (!empty($this->item->pagination) && !$this->item->paginationposition && !$this->item->paginationrelative) :
            echo $this->item->pagination;
        endif;
        ?>
        <?php if (isset($this->item->toc)) :
            echo $this->item->toc;
        endif; ?>
    <div class="com-content-article__body">
        <?php echo $this->item->text; ?>
    </div>

        <?php if ($info == 1 || $info == 2) : ?>
            <?php if ($useDefList) : ?>
                <?php echo LayoutHelper::render('joomla.content.info_block', ['item' => $this->item, 'params' => $params, 'position' => 'below']); ?>
            <?php endif; ?>
            <?php if ($params->get('show_tags', 1) && !empty($this->item->tags->itemTags)) : ?>
                <?php $this->item->tagLayout = new FileLayout('joomla.content.tags'); ?>
                <?php echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php
        if (!empty($this->item->pagination) && $this->item->paginationposition && !$this->item->paginationrelative) :
            echo $this->item->pagination;
            ?>
        <?php endif; ?>
        <?php if ((int) $params->get('urls_position', 0) === 1) : ?>
            <?php echo $this->loadTemplate('links'); ?>
        <?php endif; ?>
        <?php // Optional teaser intro text for guests ?>
    <?php elseif ($params->get('show_noauth') == true && $user->guest) : ?>
        <?php echo LayoutHelper::render('joomla.content.intro_image', $this->item); ?>
        <?php echo HTMLHelper::_('content.prepare', $this->item->introtext); ?>
        <?php // Optional link to let them register to see the whole article. ?>
        <?php if ($params->get('show_readmore') && $this->item->fulltext != null) : ?>
            <?php $menu = Factory::getApplication()->getMenu(); ?>
            <?php $active = $menu->getActive(); ?>
            <?php $itemId = $active->id; ?>
            <?php $link = new Uri(Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId, false)); ?>
            <?php $link->setVar('return', base64_encode(RouteHelper::getArticleRoute($this->item->slug, $this->item->catid, $this->item->language))); ?>
            <?php echo LayoutHelper::render('joomla.content.readmore', ['item' => $this->item, 'params' => $params, 'link' => $link]); ?>
        <?php endif; ?>
    <?php endif; ?>
    <?php
    if (!empty($this->item->pagination) && $this->item->paginationposition && $this->item->paginationrelative) :
        echo $this->item->pagination;
        ?>
    <?php endif; ?>
    <?php // Content is generated by content plugin event "onContentAfterDisplay" ?>
    <?php echo $this->item->event->afterDisplayContent; ?>
</div>
