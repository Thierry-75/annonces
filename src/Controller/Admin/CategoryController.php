<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/categorie',name:'admin_category_')]
final class CategoryController extends AbstractController
{
    #[Route('/', name: 'home',methods: ['GET'])]
    public function index(CategorieRepository $categorieRepository): Response
    {
        try {
            return $this->render('admin/categorie/index.html.twig', [
                'categories' => $categorieRepository->findAll()
            ]);
        }catch(EntityNotFoundException $e){
            return $this->redirectToRoute('app_error',['exception'=>$e]);
        }
    }

    #[Route('/add', name: 'add',methods: ['GET','POST'])]
    public function addCategory(Request $request,ValidatorInterface $validator,
              SluggerInterface $slugger,EntityManagerInterface $entityManager): Response
    {
        $category = new Categorie();
        $form = $this->createForm(CategorieType::class,$category);
        $form->handleRequest($request);
        if($request->isMethod('POST')){
            $errors = $validator->validate($request);
            if(count($errors)>0){
                return $this->render('admin/categorie/edit.html.twig',['form'=>$form->createView(),'errors'=>$errors]);
            }
            if($form->isSubmitted() && $form->isValid()){
                try{
                    $category->setSlug($slugger->slug(strtolower($form->get('name')->getData())));
                    $entityManager->persist($category);
                    $entityManager->flush();
                    $this->addFlash('alert-succes','Category created');
                    return $this->redirectToRoute('admin_category_home');
                }catch(EntityNotFoundException $e){
                    return $this->redirectToRoute('app_error',['exception'=>$e]);
                }
            }
        }
        return $this->render('admin/categorie/edit.html.twig',['form'=>$form->createView()]);
    }

    #[Route('/modify/{id}', name: 'modify',methods: ['GET','POST'])]
    public function editCategory(Categorie $categorie ,Request $request,ValidatorInterface $validator,
                                SluggerInterface $slugger,EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategorieType::class,$categorie);
        $form->handleRequest($request);
        if($request->isMethod('POST')){
            $errors = $validator->validate($request);
            if(count($errors)>0){
                return $this->render('admin/categorie/edit.html.twig',['form'=>$form->createView(),'errors'=>$errors]);
            }
            if($form->isSubmitted() && $form->isValid()){
                try{
                    $categorie->setSlug($slugger->slug(strtolower($form->get('name')->getData())));
                    $entityManager->persist($categorie);
                    $entityManager->flush();
                    $this->addFlash('alert-succes','Category modify');
                    return $this->redirectToRoute('admin_category_home');
                }catch(EntityNotFoundException $e){
                    return $this->redirectToRoute('app_error',['exception'=>$e]);
                }
            }
        }
        return $this->render('admin/categorie/edit.html.twig',['form'=>$form->createView()]);
    }
}
