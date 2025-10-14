<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileManager
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    public function upload(UploadedFile $file, string $dir, string $name, ?string $oldRessourceToDelete = null): string
    {
        $name = $this->slugger->slug($name) . '-' . uniqid() . '.' . $file->guessExtension();
        $file->move($dir, $name);

        if ($oldRessourceToDelete) {
            $this->delete($dir, $oldRessourceToDelete);
        }

        return $name;
    }

    public function uploadPoster(UploadedFile $photoSortie, string $dir, string $name, ?string $oldRessourceToDelete = null): string
    {
        $name = $this->slugger->slug($name) . '-' . uniqid() . '.' . $photoSortie->guessExtension();
        $photoSortie->move($dir, $name);

        if ($oldRessourceToDelete) {
            $this->delete($dir, $oldRessourceToDelete);
        }

        return $name;
    }

    public function uploadPhoto(UploadedFile $photo, string $dir, string $name, string $oldRessourceToDelete = null): string
    {
        $name = $this->slugger->slug($name) . '-' . uniqid() . '.' . $photo->guessExtension();
        $photo->move($dir, $name);

        if ($oldRessourceToDelete) {
            $this->delete($dir, $oldRessourceToDelete);
        }

        return $name;
    }

    public function delete(string $dir, string $name): bool
    {
        if (\file_exists($dir . '/' . $name)) {
            unlink($dir . '/' . $name);
            return true;
        }

        return false;
    }
}