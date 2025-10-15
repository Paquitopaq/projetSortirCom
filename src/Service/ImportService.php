<?php

namespace App\Service;

use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SortieService $sortieService,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function importerParticipants(UploadedFile $csvFile, Sortie $sortie): array
    {
        $messages = [];
        $lines = file($csvFile->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return [['type' => 'danger', 'text' => "Le fichier CSV est vide ou illisible."]];
        }

        $lines[0] = preg_replace('/\x{FEFF}/u', '', $lines[0]);

        foreach ($lines as $index => $line) {
            if ($index === 0) continue;

            $line = trim($line);

// Supprime BOM UTF-8 si présent
            $line = preg_replace('/\x{FEFF}/u', '', $line);

// Corrige les guillemets doubles
            $line = str_replace('""', '"', $line);

// Supprime les guillemets superflus en début/fin
            $line = preg_replace('/^"+|"+$/', '', $line);

// Parse la ligne
            $row = str_getcsv($line, ',', '"');

// Force 10 colonnes (remplit avec des chaînes vides si nécessaire)
            $row = array_pad($row, 10, '');


            if (count($row) === 1) {
                $messages[] = $this->traiterEmailUniquement($row[0], $sortie, $index);
            } elseif (count($row) >= 10) {
                $messages[] = $this->traiterParticipantComplet($row, $sortie, $index);
            } else {
                $messages[] = ['type' => 'danger', 'text' => "Ligne invalide ($index) : $line"];
                continue;
            }
        }

        $this->em->flush();
        return $messages;
    }

    private function traiterEmailUniquement(string $email, Sortie $sortie, int $index): array
    {
        if (!$email) {
            return ['type' => 'danger', 'text' => "Email manquant à la ligne $index"];
        }

        $participant = $this->em->getRepository(Participant::class)->findOneBy(['email' => $email]);

        if ($participant && $this->sortieService->inscrireParticipant($sortie, $participant)) {
            return ['type' => 'success', 'text' => "Inscription réussie pour $email"];
        }

        return ['type' => 'danger', 'text' => "Échec d'inscription pour $email"];
    }

    private function traiterParticipantComplet(array $row, Sortie $sortie, int $index): array
    {
        try {
            [$email, $roles, $password, $pseudo, $nom, $prenom, $telephone, , $actif, $photoProfil] = $row;

            if (!$email) {
                return ['type' => 'danger', 'text' => "Email manquant à la ligne $index"];
            }

            $participant = $this->em->getRepository(Participant::class)->findOneBy(['email' => $email]);

            if (!$participant) {
                $existingPseudo = $this->em->getRepository(Participant::class)->findOneBy(['pseudo' => $pseudo]);
                if ($existingPseudo && $existingPseudo->getEmail() !== $email) {
                    return ['type' => 'info', 'text' => "Ligne $index ignorée : pseudo déjà utilisé par un autre compte."];
                }

                $participant = new Participant();
                $participant->setEmail($email);
                $participant->setRoles(json_decode($roles, true) ?: ['ROLE_USER']);
                $participant->setPassword($password);
                $participant->setPseudo($pseudo);
                $participant->setNom($nom);
                $participant->setPrenom($prenom);
                $participant->setTelephone($telephone);
                $participant->setActif($actif === '1');
                $participant->setPhotoProfil($photoProfil ?: null);

                $this->em->persist($participant);
            }

            if ($this->sortieService->inscrireParticipant($sortie, $participant)) {
                return ['type' => 'success', 'text' => "Inscription réussie pour $email"];
            }

            return ['type' => 'danger', 'text' => "Échec d'inscription pour $email"];

        } catch (\Throwable $e) {
            return ['type' => 'danger', 'text' => "Erreur à la ligne $index : " . $e->getMessage()];
        }
    }
}