<?php

namespace VagKaefer\LaravelSimplifiedTranslationManager;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\ConsoleOutput;
use ZipArchive;

class Manager
{
    protected $config;

    public function __construct()
    {
        $this->config = collect([
            'alphabetize_english' => config('simplified-translation-manager.alphabetize_english'),
            'alphabetize_output_files' => config('simplified-translation-manager.alphabetize_output_files'),
            'backup_original_files' => config('simplified-translation-manager.backup_original_files'),
            'prefix' => config('simplified-translation-manager.prefix'),
            'suffix' => config('simplified-translation-manager.suffix'),
        ]);
    }

    public function process()
    {
        $languages = $this->getAllLanguagesFolders();

        if ($this->config->get('backup_original_files')) {
            (new ManagerBackup())->createZipBackup($languages);
        }

        $englishFiles = $this->getEnglishFiles();

        if ($this->config->get('alphabetize_english')) {
            $this->alphabetizeEnglishFiles($englishFiles);
        }

        foreach ($languages as $language) {
            $this->mergeLanguages($englishFiles, $language);
        }
    }

    private function alphabetizeEnglishFiles(array $englishFiles): void
    {
        echo "Sorting alphabetically english files:\n";

        foreach ($englishFiles as $englishPath) {
            $this->sortEnglishFile($englishPath);
        }
    }

    private function sortEnglishFile(string $englishPath): void
    {
        $englishTranslations = include Storage::disk('translations')->path('')."/{$englishPath}";

        $this->sortIndexesRecursively($englishTranslations);

        $exportTranslations = $this->convertArraySyntax($englishTranslations);

        if (File::put(Storage::disk('translations')->path('')."/{$englishPath}", "<?php\n\nreturn {$exportTranslations};\n")) {
            echo "\tFile lang/{$englishPath} sorted alphabetically\n";
        } else {
            $this->haltWithError("\tError during sorting alphabetically lang/{$englishPath}! Process stopped!");
        }
    }

    private function sortIndexesRecursively(array &$array): bool
    {
        array_walk($array, fn (&$value) => is_array($value) && $this->sortIndexesRecursively($value));

        return ksort($array);
    }

    private function mergeLanguages(array $englishFiles, string $language): void
    {
        echo "Merging with language '{$language}'\n";

        foreach ($englishFiles as $englishPath) {
            $this->mergeLanguageFile($englishPath, $language);
        }
    }

    private function mergeLanguageFile(string $englishPath, string $language): void
    {
        $englishTranslations = include Storage::disk('translations')->path('')."/{$englishPath}";

        $newLanguagePath = str_replace('en/', "{$language}/", $englishPath);
        $newLanguageTranslations = $this->getLanguageTranslations($newLanguagePath);

        $newLanguageTranslations = $this->mergeTranslationsRecursively($englishTranslations, $newLanguageTranslations);

        if ($this->config->get('alphabetize_output_files')) {
            $this->sortIndexesRecursively($newLanguageTranslations);
        }

        $exportTranslations = $this->convertArraySyntax($newLanguageTranslations);

        if (File::put(Storage::disk('translations')->path('')."/{$newLanguagePath}", "<?php\n\nreturn {$exportTranslations};\n")) {
            echo "\tFile lang/{$englishPath} merged with lang/{$newLanguagePath}\n";
        } else {
            $this->haltWithError("\tError merging lang/{$newLanguagePath}! Process stopped!");
        }
    }

    private function mergeTranslationsRecursively(array $base, array $new): array
    {
        foreach ($base as $key => $value) {
            $new[$key] = $this->mergeTranslationValue($key, $value, $new);
        }

        return $new;
    }

    private function mergeTranslationValue(string $key, $baseValue, array $new): mixed
    {
        if (!array_key_exists($key, $new)) {
            return is_array($baseValue)
                ? $this->mergeTranslationsRecursively($baseValue, [])
                : $this->config->get('prefix') . $baseValue . $this->config->get('suffix');
        }

        return is_array($baseValue)
            ? $this->mergeTranslationsRecursively($baseValue, $new[$key])
            : $new[$key];
    }

    private function getLanguageTranslations(string $newLanguagePath): array
    {
        return Storage::disk('translations')->exists($newLanguagePath)
            ? include Storage::disk('translations')->path('')."/{$newLanguagePath}"
            : [];
    }

    private function getEnglishFiles(): array
    {
        $files = Storage::disk('translations')->allFiles('en');

        if (empty($files)) {
            $this->haltWithError("There are no English files to manage, stopping process!");
        }

        return $files;
    }

    private function getAllLanguagesFolders(): array
    {
        $directories = Storage::disk('translations')->allDirectories();

        $directories = array_values(array_diff($directories, ['en', 'backups']));

        if (empty($directories)) {
            $this->haltWithError("There are no other languages to translate, stopping process!");
        }

        return $directories;
    }

    private function haltWithError(string $message): void
    {
        (new ConsoleOutput())->writeln("<error>{$message}</error>");

        exit(0);
    }

    private function convertArraySyntax(array $input, int $depth = 0): string
    {
        $output = array_map(
            fn ($key, $value) => $this->formatArrayItem($key, $value, $depth),
            array_keys($input),
            $input
        );

        return $this->wrapArrayOutput($output, $depth);
    }

    private function formatArrayItem(string $key, $value, int $depth): string
    {
        return sprintf(
            "%s'%s' => %s",
            str_repeat('    ', $depth + 1),
            $key,
            is_array($value) ? $this->convertArraySyntax($value, $depth + 1) : var_export($value, true)
        );
    }

    private function wrapArrayOutput(array $output, int $depth): string
    {
        $indentation = str_repeat('    ', $depth);

        return sprintf("[%s%s%s%s]", PHP_EOL, implode(',' . PHP_EOL, $output), PHP_EOL, $indentation);
    }
}