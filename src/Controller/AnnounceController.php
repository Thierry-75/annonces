<?php

namespace App\Controller;


use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/announces',name:'announce_')]
final class AnnounceController extends AbstractController
{
    /**
     * @param AnnonceRepository $annonceRepository
     * @param Request $request
     * @return Response
     */
    #[Route('/', name: 'list')]
    public function index(AnnonceRepository $annonceRepository, Request $request): Response
    {
        // nombre d'elements par page
        $limit =3;

        $page = (int)$request->query->get('page',1);

        $announces = $annonceRepository->findPaginateAnnounces($page,$limit);

        $total = $annonceRepository->findTotalAnnounces();

        return $this->render('announce/index.html.twig', [
            'announces'=>$announces,'total'=>$total,'limit'=>$limit,'page'=>$page]);
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
