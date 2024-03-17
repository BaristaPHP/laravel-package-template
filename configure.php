<?php

const VENDOR = 'Barista';

/**
 * Get the composer.json file
 *
 * @param string $question
 * @param string $default
 * @return string
 */
function ask(string $question, string $default = ''): string
{
    $answer = readline($question . ($default ? " ({$default})" : null) . ': ');

    if (!$answer) {
        return $default;
    }

    return $answer;
}

/**
 * Run a command and return the output
 *
 * @param string $command
 * @return string
 */
function run(string $command): string
{
    return trim((string)shell_exec($command));
}

/**
 * Write a line to the console
 *
 * @param string $line
 * @return void
 */
function writeln(string $line): void
{
    echo $line . PHP_EOL;
}

/**
 * Get the composer.json file contents
 *
 * @return mixed
 */
function getComposerContents(): array
{
    return json_decode(file_get_contents('composer.json'), true);
}

/**
 * @param mixed $composer
 * @return void
 */
function updateComposerContents(mixed $composer): void
{
    file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Set the author information in the composer.json file
 *
 * @return void
 */
function setAuthorInfo($composer): void
{
    $composer['authors'] = [
        [
            'name' => VENDOR,
            'email' => 'contact@barista-php.com',
        ],
    ];

    UpdateComposerContents($composer);
}

function setPackageInfo($composer, ...$info): array
{
    $composer['name'] = strtolower(VENDOR)."/$info[0]";
    $composer['description'] = $info[1];

    return $composer;
}

/**
 * Update the namespace in the given array
 *
 * @param $array
 * @param $vendor
 * @param $package
 * @return mixed
 */
function updateNamespace(&$array, $vendor, $packageName): array
{
    $package = str_replace('-', '', ucwords($packageName, '-'));

    foreach ($array as $namespace => $path) {
        if (str_contains($namespace, 'Vendor\\Package\\')) {
            unset($array[$namespace]);
            $namespace = str_replace('Vendor\\Package\\', $vendor . '\\' . $package . '\\', $namespace);
            $array[$namespace] = $path;
        }
    }
    return $array;
}

$composer = getComposerContents();

setAuthorInfo($composer);

$packageName = ask('Package Name');
$packageDescription = ask('Package Description');

$composer = setPackageInfo($composer, $packageName, $packageDescription);

updateNamespace($composer['autoload']['psr-4'], VENDOR, $packageName);
updateNamespace($composer['autoload-dev']['psr-4'], VENDOR, $packageName);

updateComposerContents($composer);
