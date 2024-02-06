<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, [
                'attr' => [
                    'style' => 'height: 6rem',
                ],
            ])
            ->add('price', TextType::class, [
                'label' => 'Price in cents',
                ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn-success mt-4 float-start',
                ],
                'label' => 'Add Entry',
                ])
            ->add('cancel', ButtonType::class, [
                'attr' => [
                    'class' => 'btn-danger mt-4 ms-2',
                    'onclick' => "window.location.href='https://127.0.0.1:8000/products'",
                ],
                'label' => 'Cancel',
                ])
            ->add('submitDraft', SubmitType::class, [
                'attr' => [
                    'class' => 'btn-warning mt-2',
                ],
                'label' => 'Save as Draft',
                ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
