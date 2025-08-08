<?php

namespace App\Controller;

use App\Entity\Annonce;
use App\Entity\Photo;
use App\Form\AnnonceType;
use App\Form\ChangePasswordType;
use App\Form\EditProfileType;
use App\Repository\UserRepository;
use App\Service\PhotoService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;
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
    protected const  ANNOUNCES = "announces";
    protected const  WEBMASTER = 'webmaster@my-domain.org';

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
        SluggerInterface $slugger,EntityManagerInterface $entityManager,PhotoService $photoService
    ): Response
    {
        $announce = new Annonce();
        $form = $this->createForm(AnnonceType::class,$announce);
        $form->handleRequest($request);
        if($request->isMethod('POST')){
            $errors = $validator->validate($request);
            if(count($errors)>0){
                return $this->render('user/annonce/add.html.twig',['form'=>$form->createView(),'errors'=>$errors]);
            }
            if($form->isSubmitted() && $form->isValid()){
                $photos = $form->get('photos')->getData();
                $count = 1;
                foreach ($photos as $photo){
                    if($photo->getClientOriginalExtension()==='jpeg' || $photo->getClientOriginalExtension()==='jpg'){
                        try{
                            $announce->setUser($this->getUser());
                            $announce->setSlug($slugger->slug(strtolower($form->get('title')->getData())));
                            $fichier = $photoService->add($photo,$announce->getSlug().'-'.$count,self::ANNOUNCES,1000,1000);
                            $image = new Photo();
                            $image->setName($fichier);
                            $announce->addPhoto($image);
                            $count++;
                        }catch (\Exception $e){
                            return $this->redirectToRoute('app_error',['exception'=>$e]);
                        }
                    }
                }
                try {
                    $entityManager->persist($announce);
                    $entityManager->flush();
                    $this->addFlash('alert-success', 'Announce has been created.');
                    return $this->redirectToRoute('announce_show',['slug'=>$announce->getSlug()]);
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

    #[Route('/data/download', name: 'data_download',methods: ['GET'])]
    public function userDataDownload(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if(!$this->getUser()){
            $this->addFlash('alert-danger','Forbidden access, only for users connected ');
        }

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont','Arial');
        $pdfOptions->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($pdfOptions);
        $context = stream_context_create([
            'ssl'=>[
                'verify_peer'=>FALSE,
                'verify_peer_name'=>FALSE,
                'allow_self_signed'=>TRUE
            ]
        ]);
        $dompdf->setHttpContext($context);
        //generation du html
        $html = $this->renderView('user/download.html.twig');
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','portrait');
        $dompdf->render();
        $user = $this->getUser();
        $fichier = 'user-data-'.$user->getFirstName().'-'.$user->getName().'.pdf';
        $dompdf->stream($fichier, [
            'Attachement'=>true
        ]);
        return new Response();
    }

}
