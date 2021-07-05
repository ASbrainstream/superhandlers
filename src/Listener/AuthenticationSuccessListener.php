<?php
/**
 * Created by PhpStorm.
 * User: brainstream
 * Date: 1/7/21
 * Time: 4:56 PM
 */

namespace App\Listener;


use http\Cookie;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class AuthenticationSuccessListener
{
    private $secure = false;
    private $tokenTtl;

    public function __construct($tokenTtl)
    {
        $this->tokenTtl = $tokenTtl;
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event)
    {
        $response = $event->getResponse();
        $data = $event->getData();

        $token = $data['token'];
        //unset($data['token']);
        $event->setData($data);

        $response->headers->setCookie(
            new \Symfony\Component\HttpFoundation\Cookie('BEARER', $token ,
                (new \DateTime())
                    ->add(new \DateInterval('PT' . $this->tokenTtl . 'S'))
            ), '/', null, $this->secure
        );
    }
}