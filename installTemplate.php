<?php
/**
 * Script to download the latest version of the tpl_generico template and install/update it.
 *
 * @version 2.0.0
 * @author Jules
 * @license GPL-2.0-or-later
 */

// --- Basic Configuration ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutes for download and extraction

// --- Constants ---
define('JOOMLA_ROOT', __DIR__);
define('UPDATE_XML_URL', 'https://apps.sobieskiproducoes.com.br/tpl_generico/atualizacao.xml');
define('ZIP_FILE', 'tpl_generico.zip');
define('TEMP_EXTRACT_DIR', 'tpl_generico_temp');

// --- Helper Functions ---

/**
 * Displays a status message.
 *
 * @param string $message The message to display.
 * @param bool $isError   Whether the message is an error.
 */
function showMessage(string $message, bool $isError = false): void
{
    if ($isError) {
        echo '<p style="color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 5px;"><strong>Error:</strong> ' . htmlspecialchars($message) . '</p>';
    } else {
        echo '<p style="color: #155724; background-color: #d4edda; padding: 10px; border-radius: 5px;">' . htmlspecialchars($message) . '</p>';
    }
    flush();
}

/**
 * Displays an error message and terminates the script.
 *
 * @param string $message The error message to display.
 */
function fail(string $message): void
{
    showMessage($message, true);
    echo "</body></html>";
    exit;
}

/**
 * Ensures a directory exists, creating it recursively if needed.
 */
function ensureDirectory(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        fail("Could not create directory: " . $dir);
    }
}

/**
 * Copies a single file, creating the destination directory if needed.
 */
function copySingleFile(string $source, string $destination): void
{
    ensureDirectory(dirname($destination));
    if (!copy($source, $destination)) {
        fail("Could not copy file: " . $source . " to " . $destination);
    }
}

/**
 * Recursively copies files and directories from a source to a destination.
 */
function recursiveCopy(string $source, string $destination): void
{
    if (!file_exists($source)) {
        return;
    }
    if (!is_dir($source)) {
        copySingleFile($source, $destination);
        return;
    }
    ensureDirectory($destination);
    $files = new DirectoryIterator($source);
    foreach ($files as $file) {
        if ($file->isDot() || !$file->isReadable()) {
            continue;
        }
        recursiveCopy($file->getRealPath(), rtrim($destination, '/') . '/' . $file->getFilename());
    }
}

/**
 * Recursively deletes a directory and its contents.
 */
function deleteDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $files = new DirectoryIterator($dir);
    foreach ($files as $file) {
        if ($file->isDot()) {
            continue;
        }
        if ($file->isDir()) {
            deleteDirectory($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
}

// --- Main Installation Logic ---

// Start HTML output
header("Content-Type: text/html; charset=UTF-8");
echo "<!DOCTYPE html><html><head><title>Template Installation</title><style>body { font-family: sans-serif; padding: 20px; line-height: 1.6; } h1 { color: #1F4E79; }</style></head><body>";
echo "<h1>Template Installation</h1>";

// 1. Check for required PHP extensions
if (!function_exists('simplexml_load_string')) {
    fail("The 'SimpleXML' PHP extension is required.");
}
if (!function_exists('curl_init') && !ini_get('allow_url_fopen')) {
    fail("Either the 'cURL' PHP extension must be enabled, or 'allow_url_fopen' must be set to 'On' in php.ini.");
}
if (!class_exists('ZipArchive')) {
    fail("The 'ZipArchive' PHP extension is required.");
}

// --- Phase 1: Download latest version ---
showMessage("Fetching update information from " . UPDATE_XML_URL . "...");

$xmlContent = @file_get_contents(UPDATE_XML_URL);
if ($xmlContent === false) {
    fail("Could not fetch the update XML file. Please check the server's internet connection and firewall settings.");
}

$updates = @simplexml_load_string($xmlContent);
if ($updates === false) {
    fail("Could not parse the update XML file. It may be malformed.");
}

$latestVersion = '0.0.0';
$latestUrl = '';

foreach ($updates->update as $update) {
    if (isset($update->version, $update->downloads->downloadurl)
        && version_compare((string) $update->version, $latestVersion, '>')) {
        $latestVersion = (string) $update->version;
        $latestUrl = (string) $update->downloads->downloadurl;
    }
}

if (empty($latestUrl)) {
    fail("Could not find a valid download URL in the update XML file.");
}

showMessage("Latest version found: {$latestVersion}. Downloading from: {$latestUrl}");

$zipFilePath = JOOMLA_ROOT . '/' . ZIP_FILE;
$downloadedData = @file_get_contents($latestUrl);
if ($downloadedData === false) {
    fail("Failed to download the template ZIP file from the specified URL.");
}
if (@file_put_contents($zipFilePath, $downloadedData) === false) {
    fail("Failed to save the downloaded ZIP file. Check file permissions for the Joomla root directory.");
}

showMessage("Successfully downloaded and saved as '" . ZIP_FILE . "'.");


// --- Phase 2: Install from ZIP ---

// 2. Check if the ZIP file exists (it should, we just downloaded it)
if (!file_exists($zipFilePath)) {
    fail("The template package '" . ZIP_FILE . "' was not found after download. An unknown error occurred.");
}

// 3. Create a temporary directory for extraction
$tempDir = JOOMLA_ROOT . '/' . TEMP_EXTRACT_DIR;
if (is_dir($tempDir)) {
    deleteDirectory($tempDir); // Clean up previous attempts
}
if (!mkdir($tempDir, 0755, true)) {
    fail("Could not create temporary directory: " . $tempDir);
}

// 4. Unzip the template package
$zip = new ZipArchive();
if ($zip->open($zipFilePath) !== true) {
    fail("Could not open the ZIP file: " . ZIP_FILE);
}
if (!$zip->extractTo($tempDir)) {
    fail("Could not extract the ZIP file to the temporary directory.");
}
$zip->close();
showMessage("Successfully extracted '" . ZIP_FILE . "' to a temporary directory.");

// 5. Define source and destination paths
$sourceBase = $tempDir;
$destinationPaths = [
    'css' => JOOMLA_ROOT . '/media/templates/site/tpl_generico/css',
    'js' => JOOMLA_ROOT . '/media/templates/site/tpl_generico/js',
    'images' => JOOMLA_ROOT . '/media/templates/site/tpl_generico/images',
    'language' => JOOMLA_ROOT . '/language',
    'html' => JOOMLA_ROOT . '/templates/tpl_generico/html',
    'component.php' => JOOMLA_ROOT . '/templates/tpl_generico/component.php',
    'error.php' => JOOMLA_ROOT . '/templates/tpl_generico/error.php',
    'index.php' => JOOMLA_ROOT . '/templates/tpl_generico/index.php',
    'offline.php' => JOOMLA_ROOT . '/templates/tpl_generico/offline.php',
    'joomla.asset.json' => JOOMLA_ROOT . '/templates/tpl_generico/joomla.asset.json',
    'templateDetails.xml' => JOOMLA_ROOT . '/templates/tpl_generico/templateDetails.xml',
];

// 6. Copy files to their final destinations
echo "<p>Starting to copy files...</p><ul>";
foreach ($destinationPaths as $sourceName => $destination) {
    $sourcePath = $sourceBase . '/' . $sourceName;
    $sourceDir = is_dir($sourcePath);
    $sourceFile = file_exists($sourcePath);

    // Check if the source exists as a directory or file.
    if ($sourceDir || $sourceFile) {
        $files_in_dir = $sourceDir ? new DirectoryIterator($sourcePath) : [$sourcePath];
        $is_empty = $sourceDir ? iterator_count($files_in_dir) <= 2 : false; // Check if directory is empty (contains only '.' and '..')

        // Skip empty directories
        if ($sourceDir && $is_empty) {
            echo "<li>Source '{$sourceName}' is an empty directory, skipping.</li>";
            continue;
        }

        recursiveCopy($sourcePath, $destination);
        echo "<li>Copied '{$sourceName}' to '{$destination}'</li>";
    } else {
        echo "<li>Source '{$sourceName}' not found in ZIP, skipping.</li>";
    }
}
echo "</ul><p>File copying complete.</p>";

// 7. Clean up
showMessage("Cleaning up temporary files...");
deleteDirectory($tempDir);
if (!unlink($zipFilePath)) {
    fail("Could not delete the ZIP file: " . ZIP_FILE . ". Please remove it manually.");
}
showMessage("Deleted temporary directory and '" . ZIP_FILE . "'.");

// --- Success Message ---
showMessage("The <strong>tpl_generico</strong> template has been successfully installed or updated to version {$latestVersion}!");
echo "</body></html>";

exit;
