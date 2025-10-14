<?php

namespace App\Form;

use App\Entity\GroupePrive;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Repository\GroupePriveRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $organisateur = $options['organisateur'];
        $builder
            ->add('nom')
            ->add('dateHeureDebut', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('duree')
            ->add('dateLimiteInscription', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('nbInscriptionMax')
            ->add('infoSortie')

            // Choix d'un lieu existant
            ->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'choice_label' => 'nom', // ou 'idLieu' si tu préfères
                'label' => 'Lieu de la sortie',
                'placeholder' => 'Choisissez un lieu',
                'attr' => ['id' => 'lieu-select'],
            ])

            // Formulaire pour créer un nouveau lieu
            ->add('nouveauLieu', LieuType::class, [
                'mapped' => false,
                'required' => false,
            ])

            ->add('groupePrive', EntityType::class, [
                'class' => GroupePrive::class,
                'choice_label' => 'nomGroupe',
                'required' => false,
                'label' => 'Groupe privé existant',
                'placeholder' => 'Sélectionnez un groupe privé',
                'query_builder' => function (GroupePriveRepository $repo) use ($organisateur) {
                    return $repo->createQueryBuilder('g')
                        ->where('g.organisateur = :organisateur')
                        ->setParameter('organisateur', $organisateur);
                },
            ])


            ->add('nouveauGroupePrive', GroupePriveType::class, [
                'required' => false,
                'mapped' => false,
                'label' => false,
            ]);


//
//            // Validation : au moins un des deux doit être rempli
//            $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
//                $data = $event->getData();
//                $form = $event->getForm();
//
//                $lieuChoisi = $data->getLieu();
//                $nouveauLieu = $form->get('nouveauLieu')->getData();
//
//                if ( !$lieuChoisi && (!$nouveauLieu || !$nouveauLieu->getNom())) {
//                    $form->addError(new FormError('Veuillez choisir un lieu ou en créer un nouveau'));
//                }
//            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
            'organisateur' => null,
        ]);
    }
}
