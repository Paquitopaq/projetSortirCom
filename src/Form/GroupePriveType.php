<?php

namespace App\Form;

use App\Entity\GroupePrive;
use App\Entity\Participant;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupePriveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomGroupe', null, [
                'label' => 'Nom du groupe',
                'attr' => ['placeholder' => 'Ex: Groupe Randonnée']
            ])

            ->add('membres', EntityType::class, [
                'class' => Participant::class,
                'choice_label' => 'pseudo',
                'multiple' => true,
                'expanded' => true, // ou false pour un select multiple
                'label' => 'Participants à inclure',
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GroupePrive::class,
        ]);
    }
}
