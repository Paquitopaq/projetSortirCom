<?php

namespace App\EntityListener;

use App\Entity\Sortie;
use App\Utils\FileManager;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Sortie::class)]
class SortieListener
{
    public function __construct(private ParameterBagInterface $parameterBag, private FileManager $fileManager) {}

    public function preRemove(Sortie $sortie, PreRemoveEventArgs $event): void
    {
        $this->fileManager->delete($this->parameterBag->get('serie')['backdrop_dir'], $sortie->getPhotoSortie());
    }
}