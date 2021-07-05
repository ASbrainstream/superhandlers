<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DemoController extends AbstractController
{

    /**
     * @return Response
     * @Route("/demo")
     */
    public function index(): Response
    {
        $list = array(
            [
                'title' => "Demo One",
                'description' => "This is the description one for aakanksha"
            ],
            [
                'title' => "Demo Two",
                'description' => "This is the description one for aakanksha"
            ],
            [
                'title' => "Demo Three",
                'description' => "This is the description one for aakanksha"
            ],
            [
                'title' => "Demo Four",
                'description' => "This is the description one for aakanksha"
            ]);

        $response = new Response(json_encode($list));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
