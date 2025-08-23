<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// Don't render empty breadcrumbs
if (empty($list)) {
    return;
}

$app = Factory::getApplication();
$siteName = $app->get('sitename');

// JSON-LD Structured Data for Breadcrumbs
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [],
];

// Visual Breadcrumbs
echo '<ol class="breadcrumb">';

foreach ($list as $i => $item) {
    $position = $i + 1;

    // Add item to JSON-LD
    $jsonLd['itemListElement'][] = [
        '@type'    => 'ListItem',
        'position' => $position,
        'name'     => $item->name,
        'item'     => $item->link,
    ];

    if ($position < count($list)) {
        // Not the last item
        echo '<li class="breadcrumb-item"><a href="' . $item->link . '"><span>' . htmlspecialchars($item->name, ENT_COMPAT, 'UTF-8') . '</span></a></li>';
    } else {
        // Last item (current page)
        echo '<li class="breadcrumb-item active" aria-current="page"><span>' . htmlspecialchars($item->name, ENT_COMPAT, 'UTF-8') . '</span></li>';
    }
}

echo '</ol>';

// Render the JSON-LD script
$doc = Factory::getDocument();
$doc->addScriptDeclaration(json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'application/ld+json');
