<?php

namespace App\Form;

use App\Entity\GroupePrive;
use App\Entity\Lieu;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Repository\GroupePriveRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $organisateur = $options['organisateur'];
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la sortie',
                'attr' => [
                    'placeholder' => 'Ex: Randonnée en forêt'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom de la sortie est obligatoire'])
                ]
            ])
            ->add('dateHeureDebut', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date et heure de début',
                'input' => 'datetime_immutable',
                'html5' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (en minutes)',
                'attr' => [
                    'placeholder' => 'Ex: 120'
                ]
            ])
            ->add('dateLimiteInscription', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date limite d\'inscription',
                'input' => 'datetime_immutable',
                'html5' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('nbInscriptionMax', IntegerType::class, [
                'label' => 'Nombre maximum de participants'
            ])
            ->add('infoSortie', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Décrivez votre sortie...'
                ]
            ])
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'nom',
                'label' => 'Site organisateur',
                'placeholder' => 'Choisir un site',
                'required' => false
            ])
            ->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'choice_label' => 'nom',
                'label' => 'Lieu',
                'placeholder' => 'Choisir un lieu',
                'required' => false
            ])
            ->add('nouveauLieu', LieuType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
            ])
            ->add('photoSortie', FileType::class, [
                'label' => 'Photo de la sortie',
                'mapped' => false,
                'required' => false,
                 'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, GIF ou WebP)',
                        'maxSizeMessage' => 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). La taille maximale autorisée est {{ limit }} {{ suffix }}.',
                    ])
                ],
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
