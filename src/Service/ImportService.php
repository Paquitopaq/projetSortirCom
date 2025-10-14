<?php

namespace App\Service;

use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SortieService $sortieService
    ) {}

    /**
     * Importe les participants depuis un fichier CSV et les inscrit à une sortie.
     *
     * @param UploadedFile $csvFile
     * @param Sortie $sortie
     * @return array Liste des messages de succès ou d'erreur
     */
    public function importerEtInscrire(UploadedFile $csvFile, Sortie $sortie): array
    {
        $messages = [];

        $lines = file($csvFile->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $index => $line) {
            // Ignorer l'en-tête
            if ($index === 0) continue;

            // Nettoyage manuel de la ligne CSV
            $line = trim($line);
            $line = preg_replace('/^"(.*)"$/', '$1', $line); // Supprime les guillemets extérieurs
            $line = str_replace('""', '"', $line); // Corrige les guillemets doublés

            $row = str_getcsv($line, ',', '"');
            if (count($row) !== 9) {
                $messages[] = ['type' => 'danger', 'text' => "Ligne CSV invalide : " . $line];
                continue;
            }

            [$email, $roles, $password, $pseudo, $nom, $prenom, $telephone, $actif, $photoProfil] = $row;

            $participant = $this->em->getRepository(Participant::class)->findOneBy(['email' => $email]);

            if (!$participant) {
                $decodedRoles = json_decode($roles, true);
                if (!is_array($decodedRoles)) {
                    $messages[] = ['type' => 'danger', 'text' => "Rôles invalides pour $email : $roles"];
                    continue;
                }

                $participant = new Participant();
                $participant->setEmail($email);
                $participant->setRoles($decodedRoles);
                $participant->setPassword($password);
                $participant->setPseudo($pseudo);
                $participant->setNom($nom);
                $participant->setPrenom($prenom);
                $participant->setTelephone($telephone);
                $participant->setActif(filter_var($actif, FILTER_VALIDATE_BOOLEAN));
                $participant->setPhotoProfil($photoProfil !== "" ? $photoProfil : null);

                $this->em->persist($participant);
            }

            if ($this->sortieService->inscrireParticipant($sortie, $participant)) {
                $messages[] = ['type' => 'success', 'text' => "Inscription réussie pour $email"];
            } else {
                $messages[] = ['type' => 'danger', 'text' => "Échec d'inscription pour $email"];
            }
        }

        $this->em->flush();

        return $messages;
    }
}