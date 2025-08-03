<?php

namespace App\Controller;

use App\Entity\Annonce;
use App\Form\AnnonceType;
use App\Form\ChangePasswordType;
use App\Form\EditProfileType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/user',name:'user_')]
final class UserController extends AbstractController
{
    #[Route('/index', name: 'index',methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if(!$this->getUser()){
            $this->addFlash('alert-danger','Forbidden access, only for users connected ');
        }
        return $this->render('user/index.html.twig');
    }

    #[Route('/all', name: 'all',methods: ['GET'])]
    public function allUsers(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if(!$this->getUser()){
            $this->addFlash('alert-danger','Forbidden access, only for users connected ');
        }
        try {
            return $this->render('user/all.html.twig',['users'=>$userRepository->findAll()]);
        }catch(EntityNotFoundException $e){
            return $this->redirectToRoute('app_error',['exception'=>$e]);
        }
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

    #[Route('/profile/edit', name: 'profile_edit',methods: ['GET','POST'])]
    public function editProfile(Request $request,ValidatorInterface $validator, EntityManagerInterface $entityManager): Response
    {
        $user =$this->getUser();
        $form = $this->createForm(EditProfileType::class,$user);
        $form->handleRequest($request);
        if($request->isMethod('POST')){
            $errors = $validator->validate($request);
            if(count($errors)>0){
                return $this->render('user/editProfile.html.twig',['form'=>$form->createView(),'errors'=>$errors]);
            }
            if($form->isSubmitted() && $form->isValid()){

                try {
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $this->addFlash('alert-success', 'Profile Modified');
                    return $this->redirectToRoute('user_index');
                }catch(EntityNotFoundException $e){
                    return $this->redirectToRoute('app_error',['exception'=>$e]);
                }
            }
        }
        return $this->render('user/editProfile.html.twig',['form'=>$form->createView()]);
    }

    #[Route('/password/edit', name: 'password_edit',methods: ['GET','POST'])]
    public function editPassword(Request $request,ValidatorInterface $validator,UserPasswordHasherInterface $userPasswordHasher,EntityManagerInterface $entityManager): Response
    {
        $user =$this->getUser();
        $form = $this->createForm(ChangePasswordType::class,$user);
        $form->handleRequest($request);
        if($request->isMethod('POST')){
            $errors = $validator->validate($request);
            if(count($errors)>0){
                return $this->render('user/editPassword.html.twig',['form'=>$form->createView(),'errors'=>$errors]);
            }
            if($form->isSubmitted() && $form->isValid()){
                $user->setPassword($userPasswordHasher->hashPassword($user,$form->get('plainPassword')->getData()));
                try {
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $this->addFlash('alert-success', 'Password Modified');
                    return $this->redirectToRoute('user_index');
                }catch(EntityNotFoundException $e){
                    return $this->redirectToRoute('app_error',['exception'=>$e]);
                }
            }
        }
        return $this->render('user/editPassword.html.twig',['form'=>$form->createView()]);
    }


}
