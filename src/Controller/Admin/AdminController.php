<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Form\CategorieType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin',name:'admin_')]
final class AdminController extends AbstractController
{
    #[Route('/', name: 'home',methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/category/add', name: 'category_add',methods: ['GET','POST'])]
    public function addCategory(Request $request,ValidatorInterface $validator,
              SluggerInterface $slugger,EntityManagerInterface $entityManager): Response
    {
        $category = new Categorie();
        $form = $this->createForm(CategorieType::class,$category);
        $form->handleRequest($request);
        if($request->isMethod('POST')){
            $errors = $validator->validate($request);
            if(count($errors)>0){
                return $this->render('edit.html.twig',['form'=>$form->createView(),'errors'=>$errors]);
            }
            if($form->isSubmitted() && $form->isValid()){
                try{
                    $category->setSlug($slugger->slug(strtolower($form->get('name')->getData())));
                    $entityManager->persist($category);
                    $entityManager->flush();
                    $this->addFlash('alert-succes','Category created');
                    return $this->redirectToRoute('admin_home');
                }catch(EntityNotFoundException $e){
                    return $this->redirectToRoute('app_error',['exception'=>$e]);
                }
            }
        }
        return $this->render('edit.html.twig',['form'=>$form->createView()]);
    }
}
