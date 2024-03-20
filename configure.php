<?php

const VENDOR = 'Barista';
const EMAIL = 'contact@barista-php.com';

/**
 * Reads a line from the console and returns the input or default value.
 *
 * @param string $question
 * @param string $default
 * @return string
 */
function prompt(string $question, string $default = ''): string
{
    return readline($question . ($default ? " ({$default})" : '') . ': ') ?: $default;
}

/**
 * Reads the composer.json file and returns its contents as an array.
 *
 * @return array
 */
function getComposerContent(): array
{
    return json_decode(file_get_contents('composer.json'), true);
}

/**
 * Writes the given array to the composer.json file.
 *
 * @param array $composerData
 * @return void
 */
function updateComposerContent(array $composerData): void
{
    file_put_contents('composer.json', json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Updates the author information in the given composer data array.
 *
 * @param array $composerData
 * @return void
 */
function setAuthorInfo(array &$composerData): void
{
    $composerData['authors'] = [
        ['name' => VENDOR, 'email' => EMAIL],
    ];
}

/**
 * Updates the package information in the given composer data array.
 *
 * @param array $composerData
 * @param string $name
 * @param string $description
 * @return void
 */
function setPackageInfo(array &$composerData, string $name, string $description): void
{
    $composerData['name'] = strtolower(VENDOR) . "/$name";
    $composerData['description'] = $description;
}

/**
 * Updates the namespaces in the given autoload configuration array.
 *
 * @param array $autoloadConfig
 * @param string $vendor
 * @param string $package
 * @return void
 */
function updateNamespaces(array &$autoloadConfig, string $vendor, string $package): void
{
    foreach ($autoloadConfig as $namespace => $path) {
        if (str_contains($namespace, 'Vendor\\Package\\')) {
            unset($autoloadConfig[$namespace]);
            $namespace = str_replace('Vendor\\Package\\', "{$vendor}\\{$package}\\", $namespace);
            $autoloadConfig[$namespace] = $path;
        }
    }
}

/**
 * Updates Laravel specific configurations in the composer data.
 *
 * @param array $composerData
 * @param string $package
 * @return void
 */
function updateLaravelConfig(array &$composerData, string $package): void
{
    $updateServiceProviderAndAliases = function (&$items, $package) {
        foreach ($items as $key => $item) {
            if (str_contains($item, 'Vendor\\Package\\')) {
                $items[$key] = str_replace('Vendor\\Package\\', VENDOR . '\\' . $package . '\\', $item);
                $items[$key] = str_replace('PackageServiceProvider', $package . 'ServiceProvider', $items[$key]);
                $items[$key] = str_replace('Package', $package, $items[$key]);
            }
        }
    };

    $updateServiceProviderAndAliases($composerData['extra']['laravel']['providers'], $package);
    $updateServiceProviderAndAliases($composerData['extra']['laravel']['aliases'], $package);
}

/**
 * Returns the content of the README.md file.
 *
 * @return string
 */
function getReadmeContent(): string
{
    return file_get_contents('README.md');
}

/**
 * Updates the README.md file with the given content.
 *
 * @param string $readmeContent
 * @param $packageName
 * @param $packageDescription
 * @return void
 */
function updateReadmeContent(string $readmeContent, $packageName, $packageDescription): void
{
    $readmeContent = str_replace(':package_description', $packageDescription, $readmeContent);
    $readmeContent = str_replace(':vendor', strtolower(VENDOR), $readmeContent);
    $readmeContent = str_replace(':package', $packageName, $readmeContent);

    file_put_contents('README.md', $readmeContent);
}

/**
 * Configures the composer.json file for the new package.
 *
 * @param $packageName
 * @param $packageDescription
 * @return void
 */
function configureComposer($packageName, $packageDescription): void
{
    $composerData = getComposerContent();

    setAuthorInfo($composerData);
    setPackageInfo($composerData, $packageName, $packageDescription);

    $formattedName = str_replace('-', '', ucwords($packageName, '-'));

    updateNamespaces($composerData['autoload']['psr-4'], VENDOR, $formattedName);
    updateNamespaces($composerData['autoload-dev']['psr-4'], VENDOR, $formattedName);

    updateLaravelConfig($composerData, $formattedName);

    updateComposerContent($composerData);
}

/**
 * Returns the package information as an array.
 *
 * @return array
 */
function getPackageInfo(): array
{
    $packageName = prompt('Package Name');
    $packageDescription = prompt('Package Description');
    return array($packageName, $packageDescription);
}

/**
 * Configures the README.md file for the new package.
 *
 * @param $packageName
 * @param $packageDescription
 * @return void
 */
function configureReadme($packageName, $packageDescription): void
{
    $readmeContent = getReadmeContent();

    updateReadmeContent($readmeContent, $packageName, $packageDescription);
}

list($packageName, $packageDescription) = getPackageInfo();
configureComposer($packageName, $packageDescription);
configureReadme($packageName, $packageDescription);