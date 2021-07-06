Super Handlers
=================

It is a demo example on how to use JWT for authentication.

Requirements
------------

Symfony(https://github.com/symfony/symfony) obviously.

Installation
------------

### Add the deps for the needed bundles

Next, run the vendors script to download the bundles:

``` bash
$ bin/console composer install
$ bin/console doctrine:migrations:migrate
```
Make sure you have database created in PhpMyAdmin , It will create the user table there.

### Make changes in config/packages/security.yaml according to this

``` php
security:
    encoders:
        App\Entity\User:
            algorithm: auto

    # https://symfony.com/doc/current/security/experimental_authenticators.html
    enable_authenticator_manager: true
    # https://symfony.com/doc/current/security.html#where-do-users-come-from-user-providers
    providers:
        # used to reload user from session & other features (e.g. switch_user)
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        login:
            pattern:  ^/api/login
            stateless: true
            json_login:
                check_path:               /api/login_check
                success_handler:          lexik_jwt_authentication.handler.authentication_success
                failure_handler:          lexik_jwt_authentication.handler.authentication_failure

        refresh:
            pattern: ^/api/token/refresh/
            stateless: true

        api:
            pattern:   ^/api
            stateless: true
            guard:
                authenticators:
                    - lexik_jwt_authentication.jwt_token_authenticator

        main:
            lazy: true
            provider: app_user_provider

            # activate different ways to authenticate
            # https://symfony.com/doc/current/security.html#firewalls-authentication

            # https://symfony.com/doc/current/security/impersonating_user.html
            # switch_user: true

    # Easy way to control access for large sections of your site
    # Note: Only the *first* access control that matches will be used
    access_control:
        - { path: ^/api/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/api/register, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/api/token/refresh, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/api,       roles: IS_AUTHENTICATED_FULLY }


```

### Create a Controller under src folder and add following method on it

``` php
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
```

### Create database and schema

``` bash
$ php app/console doctrine:database:create
$ php app/console doctrine:schema:create
```

### Enable routing configuration

``` yaml
# app/config/routing.yml
AcmePizzaBundle:
    resource: "@AcmePizzaBundle/Controller/"
    type:     annotation
    prefix:   /acme-pizza
```

### Refresh asset folder

``` bash
$ php app/console assets:install web/
```

### Data fixtures (optional)

First, make sure that your db parameters are correctly set in `app/config/parameters.ini`.
You'll need to install ``Doctrine Data Fixtures`` (don't forget to add the
path to `AppKernel.php`) and then run:

``` bash
$ php app/console doctrine:fixtures:load
```

You can read about install instructions in the Symfony2 Cookbook(http://symfony.com/doc/2.0/cookbook/doctrine/doctrine_fixtures.html#setup-and-configuration)

Usage
-----

Go to `app_dev.php/acme-pizza/pizza/list` and start selling pizzas.

Testing
-------

You can launch functional tests with Selenium RC server running with the following
steps:

-   download [selenium server](http://selenium.googlecode.com/files/selenium-server-standalone-2.2.0.jar)
-   edit `app/phpunit.xml.dist`:
    -   add php's server variable to match your configuration
    -   add the selenium's browser configuration. I added [Google Chrome Portable]()
        because it's faster than ie or even firefox.

# app/phpunit.xml.dist

``` xml
# app/phpunit.xml.dist
<!-- ... -->
<php>
    <server
        name  = "KERNEL_DIR"
        value = "/var/www/AcmePizza/app/" />
    <server
        name  = "HTTP_HOST"
        value = "localhost" />
    <server
        name  = "SCRIPT_NAME"
        value = "/AcmePizza/web/app_dev.php" />
</php>
<!-- ... -->

<!-- ... -->
<selenium>
    <browser
        name    = "Google Chrome Portable"
        browser = "*custom c:\bin\GoogleChromePortable\GoogleChromePortable.exe -disable-popup-blocking -proxy-server=127.0.0.1:4444"
        host    = "127.0.0.1" /> <!-- ip of selenium RC server -->
</selenium>
<!-- ... -->
```

Now you can run test (assuming that Selenium RC is running `java -jar selenium-server-standalone-2.2.0.jar`)
with `phpunit -c app/ src/Acme/PizzaBundle/Tests/`
If you want you can submit other missing tests.
