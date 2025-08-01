<?php

namespace App\Controller;

use App\Entity\Annonce;
use App\Form\AnnonceType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/user',name:'user_')]
final class UserController extends AbstractController
{
    #[Route('/', name: 'index',methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/notice/add', name: 'notice_add',methods: ['GET','POST'])]
    public function addNotice(Request $request,ValidatorInterface $validator,
        SluggerInterface $slugger,EntityManagerInterface $entityManager
    ): Response
    {
        $notice = new Annonce();
        $form = $this->createForm(AnnonceType::class,$notice);
        $form->handleRequest($request);
        if($request->isMethod('POST')){
            $errors = $validator->validate($request);
            if(count($errors)>0){
                return $this->render('user/annonce/add.html.twig',['form'=>$form->createView(),'errors'=>$errors]);
            }
            if($form->isSubmitted() && $form->isValid()){
                $notice->setUser($this->getUser());
                $notice->setSlug($slugger->slug(strtolower($form->get('title')->getData())));
                try {
                    $entityManager->persist($notice);
                    $entityManager->flush();
                    $this->addFlash('alert-success', 'Ad created');
                    return $this->redirectToRoute('user_index');
                }catch(EntityNotFoundException $e){
                    return $this->redirectToRoute('app_error',['exception'=>$e]);
                }
            }
        }
        return $this->render('user/annonce/add.html.twig',['form'=>$form->createView()]);
    }
}
