<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/bump-version.php <version>\n");
    exit(1);
}

$version = trim((string) $argv[1]);

if (!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-._a-zA-Z0-9]+)?$/', $version)) {
    fwrite(STDERR, "Invalid version string: {$version}\n");
    exit(1);
}

$root = dirname(__DIR__);
$pluginFile = $root . '/wpupsaga.php';
$readmeFile = $root . '/readme.txt';

$pluginContents = file_get_contents($pluginFile);
$readmeContents = file_get_contents($readmeFile);

if (!is_string($pluginContents) || !is_string($readmeContents)) {
    fwrite(STDERR, "Failed to read release files.\n");
    exit(1);
}

$pluginContents = preg_replace('/^ \* Version:\s+.+$/m', ' * Version: ' . $version, $pluginContents, 1);
$readmeContents = preg_replace('/^Stable tag:\s+.+$/m', 'Stable tag: ' . $version, $readmeContents, 1);

if (!str_contains($readmeContents, '= ' . $version . ' =')) {
    $readmeContents = preg_replace(
        '/== Changelog ==\n\n/',
        "== Changelog ==\n\n= {$version} =\n\n* Release notes go here.\n\n",
        $readmeContents,
        1
    );
}

file_put_contents($pluginFile, $pluginContents);
file_put_contents($readmeFile, $readmeContents);

fwrite(STDOUT, "Updated plugin header and readme to {$version}\n");
fwrite(STDOUT, "Next: package the plugin, commit the changes, then create and push tag v{$version}.\n");