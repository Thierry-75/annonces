<?php

namespace App\EventListener;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

class MaintenanceListener
{

    public function __construct(private string $maintenance,private Environment $twig)
    {
    }
    public function onKernelRequest(requestEvent $event)
    {
        if(!file_exists($this->maintenance)){
            return;
        }
        $event->setResponse(
            new Response(
                $this->twig->render('maintenance/maintenance.html.twig'),
                Response::HTTP_SERVICE_UNAVAILABLE
            )
        );
        $event->stopPropagation();
    }
}
