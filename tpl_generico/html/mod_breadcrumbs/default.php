<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

// Don't render empty breadcrumbs
if (empty($list)) {
    return;
}

// Raiz absoluta do site, usada para tornar os 'item' do JSON-LD URLs completas
// (schema.org exige URL absoluta; link relativo desqualifica o rich result).
$siteRoot = rtrim(Uri::root(), '/');

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

    // Add item to JSON-LD. O ultimo no (pagina atual) nao recebe 'item' — e a
    // recomendacao do schema.org/Google e evita um link redundante para si mesmo.
    $node = [
        '@type'    => 'ListItem',
        'position' => $position,
        'name'     => $item->name,
    ];
    if ($position < $total && !empty($item->link)) {
        $link = (string) $item->link;
        $node['item'] = preg_match('#^https?://#i', $link)
            ? $link
            : $siteRoot . '/' . ltrim($link, '/');
    }
    $jsonLd['itemListElement'][] = $node;

    if ($position < $total) {
        // Not the last item — o href tambem e escapado (evita HTML injection no atributo).
        echo '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item->link, ENT_QUOTES, 'UTF-8') . '"><span>' . $nameEsc . '</span></a></li>';
    } else {
        // Last item (current page)
        echo '<li class="breadcrumb-item active" aria-current="page"><span>' . $nameEsc . '</span></li>';
    }
}

echo '</ol>';

// Render the JSON-LD script. JSON_HEX_TAG impede o fechamento prematuro de
// </script> via nome de item; sem PRETTY_PRINT para nao inflar o HTML.
$doc = Factory::getDocument();
$doc->addScriptDeclaration(
    json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG),
    'application/ld+json'
);
