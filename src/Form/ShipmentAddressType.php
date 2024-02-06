<?php

namespace App\Form;

use App\Entity\ShipmentAddress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShipmentAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class,[
                'attr' => [
                    'style' => 'color: #D4ADFC; background-color: #0C134F',
                ],
            ])
            ->add('lastname', TextType::class,[
                'attr' => [
                    'style' => 'color: #D4ADFC; background-color: #0C134F',
                ],
            ])
            ->add('street', TextType::class,[
                'attr' => [
                    'style' => 'color: #D4ADFC; background-color: #0C134F',
                ],
            ])
            ->add('street_number', NumberType::class,[
                'attr' => [
                    'style' => 'color: #D4ADFC; background-color: #0C134F',
                ],
            ])
            ->add('zip_code', NumberType::class,[
                'attr' => [
                    'style' => 'color: #D4ADFC; background-color: #0C134F',
                ],
            ])
            ->add('city', TextType::class,[
                'attr' => [
                    'style' => 'color: #D4ADFC; background-color: #0C134F',
                ],
            ])
            ->add('country', CountryType::class,[
                'attr' => [
                    'style' => 'color: #D4ADFC; background-color: #0C134F',
                ],
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShipmentAddress::class,
            'row_attr' => [
                'style' => 'color: #D4ADFC; background-color: #0C134F',
            ],
        ]);
    }
}
