<?php

namespace App\Form;

use App\Entity\Categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchAnnonceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('word',SearchType::class,['attr'=>['class'=>'input-register','placeholder'=>'keywords'],
                'label'=>false,
                'required'=>false
            ])
            ->add('category',EntityType::class,[
                'class'=>Categorie::class,
                'label'=>false,
                'placeholder'=>'Category',
                'attr'=>['class'=>'input-register'],
                'required'=>false
            ])
            ->add('Find',SubmitType::class,['attr'=>['class'=>'btn-retour-court']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
