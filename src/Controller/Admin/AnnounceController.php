<?php

namespace App\Controller\Admin;

use App\Entity\Annonce;
use App\Form\AnnonceType;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/announce',name:'admin_announce_')]
final class AnnounceController extends AbstractController
{
    #[Route('/', name: 'home',methods: ['GET'])]
    public function index(AnnonceRepository $annonceRepository): Response
    {
        try {
            return $this->render('admin/announce/index.html.twig', [
                'announces' => $annonceRepository->findAll()
            ]);
        }catch(EntityNotFoundException $e){
            return $this->redirectToRoute('app_error',['exception'=>$e]);
        }
    }

    #[Route('/show/{id}', name: 'show',methods: ['GET'])]
    public function show(Annonce $annonce): Response
    {
        try {
            return $this->render('admin/announce/show.html.twig', [
                'announce'=>$annonce
            ]);
        }catch(EntityNotFoundException $e){
            return $this->redirectToRoute('app_error',['exception'=>$e]);
        }
    }



    #[Route('/modify/{id}', name: 'modify',methods: ['GET','POST'])]
    public function editAnnounce(Annonce $annonce ,Request $request,ValidatorInterface $validator,
                                SluggerInterface $slugger,EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AnnonceType::class,$annonce);
        $form->handleRequest($request);
        if($request->isMethod('POST')){
            $errors = $validator->validate($request);
            if(count($errors)>0){
                return $this->render('admin/announce/edit.html.twig',['form'=>$form->createView(),'errors'=>$errors]);
            }
            if($form->isSubmitted() && $form->isValid()){
                try{
                    $annonce->setSlug($slugger->slug(strtolower($form->get('title')->getData())));
                    $entityManager->persist($annonce);
                    $entityManager->flush();
                    $this->addFlash('alert-success','Announce modify');
                    return $this->redirectToRoute('admin_announce_home');
                }catch(EntityNotFoundException $e){
                    return $this->redirectToRoute('app_error',['exception'=>$e]);
                }
            }
        }
        return $this->render('admin/announce/edit.html.twig',['form'=>$form->createView()]);
    }

    #[Route('/active/{id}', name: 'active',methods: ['GET'])]
    public function active(Annonce $announce,EntityManagerInterface $entityManager): Response
    {
        $announce->setActive(!$announce->isActive());
        $entityManager->persist($announce);
        $entityManager->flush();

        return new Response("true");
    }

    #[Route('/delete/{id}', name: 'delete',methods: ['GET'])]
    public function delete(Annonce $announce,EntityManagerInterface $entityManager): Response
    {

        $entityManager->remove($announce);
        $entityManager->flush();
        $this->addFlash('alert-success','Announce has been deleted.');
        return $this->redirectToRoute('admin_announce_home');
    }
}
