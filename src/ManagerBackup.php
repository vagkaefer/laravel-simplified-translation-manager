<?php

namespace VagKaefer\LaravelSimplifiedTranslationManager;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\ConsoleOutput;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ManagerBackup
{

  public function createZipBackup(array $languages): void
  {

    echo "Creating backup...";

    $this->ensureBackupsDirectoryExists();

    $zip = $this->createNewZipArchive();

    $this->addToZipArchive($zip, $languages);

    $zip->close();

    // TODO Validate if the zip is created and not empty
    echo "backup generated in " . $this->getDestinationPath() . "\n";
  }

  protected function ensureBackupsDirectoryExists(): void
  {

    if (!File::exists(Storage::disk('translations')->path('') . '/backups')) {
      Storage::disk('translations')->makeDirectory('backups');
      chmod(Storage::disk('translations')->path('') . '/backups', 0777);
    }
  }

  protected function createNewZipArchive(): ZipArchive
  {
    $zip = new ZipArchive();
    $zip->open($this->getDestinationPath(), ZipArchive::CREATE | ZipArchive::OVERWRITE);

    return $zip;
  }

  protected function getDestinationPath(): string
  {
    return Storage::disk('translations')->path('') . '/backups/' . time() . '-lang-backup.zip';
  }

  protected function addToZipArchive(ZipArchive $zip, array $languages): void
  {
    $languages[] = 'en';

    foreach ($languages as $language) {
      $source = Storage::disk('translations')->path('') . $language;

      $files = $this->getFilesInSourceDirectory($source);

      foreach ($files as $file) {
        $this->addToZipArchiveFileOrDirectory($zip, $file);
      }
    }
  }

  protected function getFilesInSourceDirectory(string $source): RecursiveIteratorIterator
  {
    return new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST
    );
  }

  protected function addToZipArchiveFileOrDirectory(ZipArchive $zip, \SplFileInfo $file): void
  {
    $filePath = $file->getRealPath();

    if (is_dir($filePath)) {
      $zip->addEmptyDir($filePath);
    } elseif (is_file($filePath)) {
      $zip->addFromString($filePath, file_get_contents($filePath));
    }
  }
}
