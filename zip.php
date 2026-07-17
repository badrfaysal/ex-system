<?php
$zip = new ZipArchive();
$filename = 'Ex-systemerp-Backup.zip';
if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) { exit('Cannot open'); }
$dir = new RecursiveDirectoryIterator('.');
$files = new RecursiveIteratorIterator($dir);
foreach ($files as $name => $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen(realpath('.')) + 1);
        if (preg_match('/^(vendor|node_modules|\.git|storage\\\\framework|Ex-systemerp-Backup\.zip)/i', $relativePath)) continue;
        $zip->addFile($filePath, $relativePath);
    }
}
$zip->close();
echo 'Done';
