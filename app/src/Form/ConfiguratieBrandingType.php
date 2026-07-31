<?php

namespace App\Form;

use App\Entity\ConfiguratieBranding;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/** @extends AbstractType<ConfiguratieBranding> */
final class ConfiguratieBrandingType extends AbstractType
{
    /** @param FormBuilderInterface<ConfiguratieBranding|null> $builder */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $logo = new File(
            maxSize: '2M',
            mimeTypes: ['image/png', 'image/jpeg', 'image/webp'],
            maxSizeMessage: 'Fișierul poate avea cel mult {{ limit }} {{ suffix }}.',
            mimeTypesMessage: 'Încărcați o imagine PNG, JPEG sau WebP validă.',
        );
        $favicon = new File(
            maxSize: '512K',
            mimeTypes: ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'],
            maxSizeMessage: 'Fișierul poate avea cel mult {{ limit }} {{ suffix }}.',
            mimeTypesMessage: 'Încărcați un favicon PNG sau ICO valid.',
        );

        $builder
            ->add('numeAplicatie', TextType::class, [
                'label' => 'Nume aplicație',
            ])
            ->add('culoarePrincipala', TextType::class, [
                'label' => 'Culoare principală',
                'attr' => ['placeholder' => '#164194'],
            ])
            ->add('culoareSecundara', TextType::class, [
                'label' => 'Culoare secundară de brand',
                'attr' => ['placeholder' => '#D41131'],
            ])
            ->add('fisierLogoPrincipal', FileType::class, [
                'label' => 'Logo principal',
                'mapped' => false,
                'required' => false,
                'constraints' => [$logo],
            ])
            ->add('fisierLogoCompact', FileType::class, [
                'label' => 'Logo compact',
                'mapped' => false,
                'required' => false,
                'constraints' => [$logo],
            ])
            ->add('fisierFavicon', FileType::class, [
                'label' => 'Favicon',
                'mapped' => false,
                'required' => false,
                'constraints' => [$favicon],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConfiguratieBranding::class,
        ]);
    }
}
