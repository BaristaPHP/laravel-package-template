<?php

const VENDOR = 'Barista';

/**
 * Reads a line from the console and returns the input or default value.
 */
function prompt(string $question, string $default = ''): string {
    $answer = readline($question . ($default ? " ({$default})" : '') . ': ');
    return $answer ?: $default;
}

/**
 * Executes a shell command and returns the trimmed output.
 */
function executeCommand(string $command): string {
    return trim(shell_exec($command));
}

/**
 * Outputs a line to the console with a newline.
 */
function outputLine(string $line): void {
    echo $line . PHP_EOL;
}

/**
 * Reads the composer.json file and returns its contents as an array.
 */
function readComposerFile(): array {
    return json_decode(file_get_contents('composer.json'), true);
}

/**
 * Writes the given array to the composer.json file.
 */
function writeComposerFile(array $composerData): void {
    file_put_contents('composer.json', json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Updates the author information in the given composer data array.
 */
function setAuthorInfo(array &$composerData): void {
    $composerData['authors'] = [
        ['name' => VENDOR, 'email' => 'contact@barista-php.com'],
    ];
}

/**
 * Updates the package information in the given composer data array.
 */
function setPackageInfo(array &$composerData, string $name, string $description): void {
    $composerData['name'] = strtolower(VENDOR) . "/$name";
    $composerData['description'] = $description;
}

/**
 * Updates the namespaces in the given autoload configuration array.
 */
function updateNamespaces(array &$autoloadConfig, string $vendor, string $package): void {
    foreach ($autoloadConfig as $namespace => $path) {
        if (str_contains($namespace, 'Vendor\\Package\\')) {
            unset($autoloadConfig[$namespace]);
            $namespace = str_replace('Vendor\\Package\\', "{$vendor}\\{$package}\\", $namespace);
            $autoloadConfig[$namespace] = $path;
        }
    }
}

/**
 * Starts the configuration process.
 */
function configureComposer(): void {
    $composerData = readComposerFile();

    $packageName = prompt('Package Name');
    $formattedName = str_replace('-', '', ucwords($packageName, '-'));
    $packageDescription = prompt('Package Description');

    setAuthorInfo($composerData);
    setPackageInfo($composerData, $packageName, $packageDescription);

    updateNamespaces($composerData['autoload']['psr-4'], VENDOR, $formattedName);
    updateNamespaces($composerData['autoload-dev']['psr-4'], VENDOR, $formattedName);

    updateLaravelConfig($composerData, $formattedName);

    writeComposerFile($composerData);
}

/**
 * Updates Laravel specific configurations in the composer data.
 */
function updateLaravelConfig(array &$composerData, string $package): void {
    $updateServiceProviderAndAliases = function (&$items, $package) {
        foreach ($items as $key => $item) {
            if (str_contains($item, 'Vendor\\Package\\')) {
                $items[$key] = str_replace('Vendor\\Package\\', VENDOR . '\\' . $package . '\\', $item);
                $items[$key] = str_replace('PackageServiceProvider', $package . 'ServiceProvider', $items[$key]);
            }
        }
    };

    $updateServiceProviderAndAliases($composerData['extra']['laravel']['providers'], $package);
    $updateServiceProviderAndAliases($composerData['extra']['laravel']['aliases'], $package, true);
}

configureComposer();