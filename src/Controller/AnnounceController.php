<?php

namespace App\Controller;

use App\Entity\Annonce;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/announces',name:'announce_')]
final class AnnounceController extends AbstractController
{
    /**
     * @param AnnonceRepository $annonceRepository
     * @return Response
     */
    #[Route('/list', name: 'list')]
    public function index(AnnonceRepository $annonceRepository): Response
    {
        return $this->render('announce/index.html.twig', [
            'announces'=>$annonceRepository->findBy(['active'=>true],['createdAt'=>'desc'])]);
    }

    /**
     * @param $slug
     * @param AnnonceRepository $annonceRepository
     * @return Response
     */
    #[Route('/show/{slug}',name:'show')]
    public function showAnnounce($slug,AnnonceRepository $annonceRepository):Response
    {
        try {
            return $this->render('announce/show.html.twig', ['announce' => $annonceRepository->findOneBy(['slug' => $slug])]);
        }catch (EntityNotFoundException $e){
            return $this->redirectToRoute('app_error',['exception'=>$e]);
        }
    }





}
