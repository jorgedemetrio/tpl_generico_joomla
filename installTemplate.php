<?php
/**
 * Script to install/update the tpl_generico template from a ZIP file.
 *
 * @version 1.0.0
 * @author Jules
 * @license GPL-2.0-or-later
 */

// --- Basic Configuration ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- Constants ---
define('JOOMLA_ROOT', __DIR__);
define('ZIP_FILE', 'tpl_generico.zip');
define('TEMP_EXTRACT_DIR', 'tpl_generico_temp');

// --- Helper Functions ---

/**
 * Displays an error message and terminates the script.
 *
 * @param string $message The error message to display.
 */
function fail(string $message): void
{
    header("HTTP/1.1 500 Internal Server Error");
    echo "<!DOCTYPE html><html><head><title>Error</title><style>body { font-family: sans-serif; background-color: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; }</style></head><body>";
    echo "<h1>Installation Failed</h1>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($message) . "</p>";
    echo "</body></html>";
    exit;
}

/**
 * Recursively copies files and directories from a source to a destination.
 *
 * @param string $source      The source path.
 * @param string $destination The destination path.
 */
function recursive_copy(string $source, string $destination): void
{
    if (!file_exists($source)) {
        return;
    }

    if (is_dir($source)) {
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true)) {
                fail("Could not create directory: " . $destination);
            }
        }
        $files = new DirectoryIterator($source);
        foreach ($files as $file) {
            if ($file->isDot() || !$file->isReadable()) {
                continue;
            }
            $sourcePath = $file->getRealPath();
            $destinationPath = rtrim($destination, '/') . '/' . $file->getFilename();
            if ($file->isDir()) {
                recursive_copy($sourcePath, $destinationPath);
            } else {
                if (!copy($sourcePath, $destinationPath)) {
                    fail("Could not copy file: " . $sourcePath . " to " . $destinationPath);
                }
            }
        }
    } else {
        // If the source is a single file, ensure the destination directory exists.
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                fail("Could not create directory: " . $dir);
            }
        }
        if (!copy($source, $destination)) {
            fail("Could not copy file: " . $source . " to " . $destination);
        }
    }
}


/**
 * Recursively deletes a directory and its contents.
 *
 * @param string $dir The directory to delete.
 */
function delete_directory(string $dir): void
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
            delete_directory($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
}

// --- Main Installation Logic ---

// 1. Check for required PHP extensions
if (!class_exists('ZipArchive')) {
    fail("The 'ZipArchive' PHP extension is required but not enabled on the server.");
}

// 2. Check if the ZIP file exists
$zipFilePath = JOOMLA_ROOT . '/' . ZIP_FILE;
if (!file_exists($zipFilePath)) {
    fail("The template package '" . ZIP_FILE . "' was not found in the Joomla root directory.");
}

// 3. Create a temporary directory for extraction
$tempDir = JOOMLA_ROOT . '/' . TEMP_EXTRACT_DIR;
if (is_dir($tempDir)) {
    delete_directory($tempDir); // Clean up previous attempts
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

echo "<h1>Template Installation</h1>";
echo "<p>Successfully extracted '" . ZIP_FILE . "' to a temporary directory.</p>";

// 5. Define source and destination paths
$sourceBase = $tempDir;
$destinationPaths = [
    // Media files
    'css' => JOOMLA_ROOT . '/media/templates/site/tpl_generico/css',
    'js' => JOOMLA_ROOT . '/media/templates/site/tpl_generico/js',
    'images' => JOOMLA_ROOT . '/media/templates/site/tpl_generico/images',
    // Language files
    'language' => JOOMLA_ROOT . '/language',
    // Template core files
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
    if (file_exists($sourcePath)) {
        recursive_copy($sourcePath, $destination);
        echo "<li>Copied '{$sourceName}' to '{$destination}'</li>";
    } else {
        echo "<li>Source '{$sourceName}' not found in ZIP, skipping.</li>";
    }
}
echo "</ul><p>File copying complete.</p>";


// 7. Clean up
echo "<p>Cleaning up temporary files...</p>";
delete_directory($tempDir);
if (!unlink($zipFilePath)) {
    fail("Could not delete the ZIP file: " . ZIP_FILE . ". Please remove it manually.");
}
echo "<p>Deleted temporary directory and '" . ZIP_FILE . "'.</p>";

// --- Success Message ---
header("HTTP/1.1 200 OK");
echo "<!DOCTYPE html><html><head><title>Success</title><style>body { font-family: sans-serif; background-color: #d4edda; color: #155724; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; }</style></head><body>";
echo "<h1>Installation Complete</h1>";
echo "<p>The <strong>tpl_generico</strong> template has been successfully installed or updated!</p>";
echo "</body></html>";

exit;
?>
