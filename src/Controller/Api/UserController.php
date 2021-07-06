<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use ContainerAxNCxdt\getLexikJwtAuthentication_CheckConfigCommandService;
use ContainerTLQpCdd\getLexikJwtAuthentication_JwtManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use FOS\RestBundle\Controller\Annotations\Version;

class UserController extends AbstractFOSRestController
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var PasswordEncoder
     */
    private  $passwordEncoder;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var JWTAuthenticator
     */
    private $jwtAuthenticator;

    public function __construct(UserRepository $userRepository, UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->em = $em;
    }

    /**
     * @return View
     * @Route("/{version}/users", name="users_index")
     */
    public function index(SerializerInterface $serialzer, Request $request): View
    {
        $users = $this->userRepository->findAll();
        dd($users);
        return $this->view([
            'users' => $serialzer->serialize($users, 'json')
        ], Response::HTTP_OK);
    }

    /**
     * @Route("/api/register", name="register", methods={"POST"})
     */
    public function register(Request $request): View
    {
        if($request->isMethod('post')){
            $email = $request->get('email');
            $password = $request->get('password');

            $user = $this->userRepository->findOneBy([
                'email' => $email
            ]);

            // If User is already exist
            if(!is_null($user)) {
                return $this->view([
                    'message' => 'User already exists'
                ], Response::HTTP_CONFLICT);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
            $user->setRoles(["ROLE_USER"]);
            $this->em->persist($user);
            $this->em->flush();

            return $this->view([
                'message' => 'User Created',
                'user' => $user->getEmail()
            ], Response::HTTP_CREATED);
        }

        return $this->view([
            'message' => 'Invalid Method'
        ], Response::HTTP_CONFLICT);
    }

    /**
     * @param Request $request
     * @return View
     * @throws \Exception
     * @Route("api/login", name="login", methods={"POST"})
     */
    public function login(Request $request, JWTTokenManagerInterface $JWTManager, Security $security): View
    {
        if($request->isMethod('post')){
            $request->request->add(json_decode($request->getContent(), true));

            if(!$request->get('email') || !$request->get('password'))
            {
                return $this->view([
                    'message' => 'Please provide valid details'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $user = $this->userRepository->findOneBy([
                'email' => $request->get('email')
            ]);

            if (!$user || !$this->passwordEncoder->isPasswordValid($user, $request->get('password'))) {
                return $this->view([
                    'message' => 'Email or Password is wrong'
                ], Response::HTTP_UNAUTHORIZED);
            }
            $payload = [
                "user" => $user->getEmail(),
                "exp"  => (new \DateTime())->modify("+5 minutes")->getTimestamp(),
            ];

           // $token = $JWTManager->create($security->getUser());

            $jwt = JWT::encode($payload, $this->getParameter('jwt_secret'), 'HS256');
            return $this->view([
                'message' => 'Success',
                'token' => sprintf('Bearer %s', $jwt)
            ], Response::HTTP_OK);
        }

        return $this->view([
            'message' => 'Invalid Method'
        ], Response::HTTP_CONFLICT);
    }
}
