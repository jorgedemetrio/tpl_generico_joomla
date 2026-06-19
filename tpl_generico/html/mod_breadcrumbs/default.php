<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

// Don't render empty breadcrumbs
if (empty($list)) {
    return;
}

// JSON-LD Structured Data for Breadcrumbs
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [],
];

// Visual Breadcrumbs
echo '<ol class="breadcrumb">';

$total = count($list);

foreach ($list as $i => $item) {
    $position = $i + 1;
    // Escape unico do nome (ENT_QUOTES, padrao do template) reutilizado abaixo.
    $nameEsc  = htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8');

    // Add item to JSON-LD
    $jsonLd['itemListElement'][] = [
        '@type'    => 'ListItem',
        'position' => $position,
        'name'     => $item->name,
        'item'     => $item->link,
    ];

    if ($position < $total) {
        // Not the last item — o href tambem e escapado (evita HTML injection no atributo).
        echo '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item->link, ENT_QUOTES, 'UTF-8') . '"><span>' . $nameEsc . '</span></a></li>';
    } else {
        // Last item (current page)
        echo '<li class="breadcrumb-item active" aria-current="page"><span>' . $nameEsc . '</span></li>';
    }
}

echo '</ol>';

// Render the JSON-LD script
$doc = Factory::getDocument();
$doc->addScriptDeclaration(json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'application/ld+json');
