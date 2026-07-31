<?php

namespace App\Form;

use App\Entity\Utilizator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<Utilizator> */
final class UtilizatorNouType extends AbstractType
{
    /** @param FormBuilderInterface<Utilizator|null> $builder */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenume', TextType::class, [
                'label' => 'Prenume',
            ])
            ->add('nume', TextType::class, [
                'label' => 'Nume',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('administrator', CheckboxType::class, [
                'label' => 'Administrator',
                'mapped' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilizator::class,
        ]);
    }
}
