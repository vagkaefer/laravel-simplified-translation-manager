<?php

namespace VagKaefer\LaravelSimplifiedTranslationManager;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\ConsoleOutput;
use RuntimeException;
use ZipArchive;

class Manager
{

  protected $alphabetize_english;
  protected $alphabetize_output_files;
  protected $backup_original_files;
  protected $prefix;
  protected $suffix;

  public function __construct()
  {
    $this->alphabetize_english = config('simplified-translation-manager.alphabetize_english');
    $this->alphabetize_output_files = config('simplified-translation-manager.alphabetize_output_files');
    $this->backup_original_files = config('simplified-translation-manager.backup_original_files');
    $this->prefix = config('simplified-translation-manager.prefix');
    $this->suffix = config('simplified-translation-manager.suffix');
  }

  public function process()
  {

    // List all Languages (excluding english "en")
    $languages = $this->getAllLanguagesFolders();

    // Create backup
    if ($this->backup_original_files) {
      $this->createZipBackup($languages);
    }

    // TODO This need to be recursive and get files inside another folders
    $englishFiles = $this->getEnglishFiles();

    if ($this->alphabetize_english) {
      $this->alphabetize_english_files($englishFiles);
    }

    // Process every language
    foreach ($languages as $language) {
      $this->mergeLanguages($englishFiles, $language);
    }
  }

  private function alphabetize_english_files($englishFiles)
  {

    echo "Sorting alphabetically english files:\n";

    foreach ($englishFiles as $englishPath) {

      $englishTranslations = include "lang/" . $englishPath;

      $this->sortIndexes($englishTranslations);

      // Change array() to [] 
      // Replace var_export with the custom function
      $exportTranslations = $this->convertArraySyntax($englishTranslations);

      // Save file
      if (File::put("lang/" . $englishPath, "<?php\n\nreturn $exportTranslations;\n")) {
        echo "\tFile lang/" . $englishPath . " sorted alphabetically\n";
      } else {
        $this->returnConsoleError("\tError during sorting alphabetically lang/" . $englishPath . "! Process stoped!");
      }
    }
  }

  private function sortIndexes(&$array)
  {

    foreach ($array as &$value) {
      if (is_array($value)) {
        $this->sortIndexes($value);
      }
    }

    return ksort($array);
  }

  private function mergeLanguages($englishFiles, $language)
  {

    echo "Mergin language '" . $language . "'\n";

    foreach ($englishFiles as $englishPath) {

      $englishTranslations = include "lang/" . $englishPath;

      $newLanguagePath = str_replace("en/", $language . "/", $englishPath);

      if (Storage::disk('translations')->exists($newLanguagePath)) {
        $newLanguageTranslations = include "lang/" . $newLanguagePath;
      } else {
        $newLanguageTranslations = [];
      }

      // Find keys in English translations that do not exist the newLanguagePath
      $newTranslations = array_diff_key($englishTranslations, $newLanguageTranslations);

      // Add the new key and TODO include prefix/suffix
      foreach ($newTranslations as $index => $newTranslation) {

        if (is_array($newTranslation)) {
          $newLanguageTranslations = Arr::add($newLanguageTranslations, $index, $newTranslation);
        } else {
          $newLanguageTranslations = Arr::add($newLanguageTranslations, $index, $this->prefix . $newTranslation . $this->suffix);
        }
      }

      // Check if the new file needs to be sorted alphabetically
      if ($this->alphabetize_output_files) {
        // Sort indexes alphabetically
        $this->sortIndexes($newLanguageTranslations);
      }

      // Change array() to [] 
      // Replace var_export with the custom function
      $exportTranslations = $this->convertArraySyntax($newLanguageTranslations);

      // Save file
      if (File::put("lang/" . $newLanguagePath, "<?php\n\nreturn $exportTranslations;\n")) {
        echo "\tFile lang/" . $englishPath . " merged with lang/" . $newLanguagePath . "\n";
      } else {
        $this->returnConsoleError("\tError merging lang/" . $newLanguagePath . "! Process stoped!");
      }
    }

    // dump($englishFiles);
    // dump($language);
  }

  private function getEnglishFiles()
  {
    // List all files in the 'en' directory of the 'translations' disk
    $files = Storage::disk('translations')->allFiles('en');

    // Check if any English files were found
    if (empty($files)) {

      // If not, output an error message and stop the process
      $this->returnConsoleError("There is not English files to manage, stopping process!");
    }

    // Return the files if found
    return $files;
  }

  private function getAllLanguagesFolders()
  {
    // List all directories in the root of the 'translations' disk
    $directories = Storage::disk('translations')->allDirectories();

    // Remove 'en' and 'backps' from the directories
    $directories = array_values(array_diff($directories, ['en', 'backups']));

    // Check if there are any other languages to translate
    if (empty($directories)) {

      // If not, output an error message and stop the process
      $this->returnConsoleError("There is not another languages to translate, stopping process!");
    }

    // Return the directories if found
    return $directories;
  }

  private function returnConsoleError($message)
  {
    $consoleOutput = new ConsoleOutput();
    $consoleOutput->writeln("<error>" . $message . "</error>");
    exit(0);
  }


  // Recursive function to convert array() syntax to []
  private function convertArraySyntax($input)
  {
    if (is_array($input)) {
      $output = [];
      foreach ($input as $key => $value) {
        $output[$key] = $this->convertArraySyntax($value);
      }
      return '[' . PHP_EOL . collect($output)->map(function ($value, $key) {
        return "    '{$key}' => {$value},";
      })->implode(PHP_EOL) . PHP_EOL . ']';
    } else {
      return var_export($input, true);
    }
  }

  private function createZipBackup($languages)
  {

    // Create new Zip Archive.
    $zip = new ZipArchive();

    // Create backups folder if not exists
    Storage::disk('translations')->makeDirectory('backups', 0777);

    // where the zip will be saved
    $destination = './lang/backups/' . time() . '-lang-backup.zip';

    // The mode to open the archive.
    $open = $zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // Check if open was successful.
    if ($open !== true) {
      $this->returnConsoleError("Could not open archive file: $destination");
    }

    $languages[] = 'en';

    foreach ($languages as $language) {

      $source = 'lang/' . $language;
      // Get files in the source directory.
      $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST
      );

      foreach ($files as $file) {

        // Get real path for current file
        $filePath = $file->getRealPath();

        if (is_dir($filePath)) {
          $zip->addEmptyDir($filePath);
        } elseif (is_file($filePath)) {
          $zip->addFromString($filePath, file_get_contents($filePath));
        }
      }
    }

    // Close the active archive.
    $zip->close();
  }
}
