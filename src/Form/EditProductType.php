<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Service\Categorys;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EditProductType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // $categorys = $this->categorys->getChoices();
        // $categorys = $this->entityManager->getRepository(Category::class)->findBy();
        $categorys = $this->entityManager->getRepository(Category::class)->findAllSubcategorys();

        $builder
        ->add('name', TextType::class)
        ->add('category', ChoiceType::class, [
            'choices' => $categorys,
            'choice_label' => 'label',
            'group_by' => function($choice)
            {
                if($parent = $choice->getParentLabel())
                {
                    return $parent;
                }
            }
        ])
        ->add('newCategory', ButtonType::class, [
            'attr' => [
                'class' => 'btn-secondary mt-1 mb-2',
                'onclick' => "newCategory()",
            ],
            'label' => 'New Category',
        ])
        ->add('description', TextareaType::class, [
            'attr' => [
                'style' => 'height: 6rem',
            ],
        ])
        ->add('price', TextType::class, [
            'label' => 'Price in cents',
            ])
        ->add('picture', FileType::class, [
            'label' => 'Product picture',
            'mapped' => false,
            'required' => false,
            'constraints' => [
                new File([
                    'maxSize' => '4096k',
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/png',
                    ],
                    'mimeTypesMessage' => 'Please upload a valid Picture format (jpeg/png)',
                ])
            ],
        ])
        ->add('draft', CheckboxType::class, [
            'label' => 'Save as Draft?',
            'attr' => [
                'onClick' => 'changeToDraft(this)'
            ],
            'required' => false,
        ])

        ->add('submit', SubmitType::class, [
            'attr' => [
                'class' => 'btn-success mt-4 float-start',
            ],
            'label' => 'Save Changes',
            ])
        ->add('cancel', ButtonType::class, [
            'attr' => [
                'class' => 'btn-warning mt-4 ms-2',
                'onclick' => "window.location.href='https://127.0.0.1:8000/products'",
            ],
            'label' => 'Cancel',
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
