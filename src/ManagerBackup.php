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

    $zipFilename = $zip->filename;
    
    $zip->close();

    $checkBackup = $this->checkBackupIsCreated($zipFilename);

    if($checkBackup){
      echo "backup generated in " . $this->getDestinationPath() . "\n";
    }else{
      $this->haltWithError("fail to generate backup - stoping process!");
    }
    
  }

  protected function checkBackupIsCreated($zipFilename): bool
  {

    $exists = File::exists($zipFilename);
    $fileSize = File::size($zipFilename);

    return ($exists && $fileSize > 100);

  }

  protected function checkBackupDirectoryExists(): bool
  {
    return File::exists(Storage::disk('translations')->path('') . 'backups');
  }

  protected function ensureBackupsDirectoryExists(): void
  {
    if (!$this->checkBackupDirectoryExists()) {
      Storage::disk('translations')->makeDirectory('backups');
      if (!$this->checkBackupDirectoryExists()) {
        $this->haltWithError("Error creating backup directory! - " . Storage::disk('translations')->path('') . "backups\n");
      }
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
    return Storage::disk('translations')->path('') . 'backups/' . time() . '-lang-backup.zip';
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

  private function haltWithError(string $message): void
    {
        (new ConsoleOutput())->writeln("<error>{$message}</error>");

        exit(0);
    }

}
