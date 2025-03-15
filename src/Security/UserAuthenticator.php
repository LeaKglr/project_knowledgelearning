<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class UserAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator, private RouterInterface $router)
    {
    }

    /**
     * Handles user authentication.
     * 
     * @param Request $request The HTTP request containing login credentials.
     * @return Passport The security passport for authentication.
     */
    public function authenticate(Request $request): Passport
    {
        // Retrieve login credentials from the request
        $email = $request->getPayload()->getString('email');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('_csrf_token');

        // Store the last username in session (for pre-filling login form)
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // Create a Passport object for authentication
        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),            ]
        );
    }

    /**
     * Handles successful authentication.
     * Redirects the user to the home page after logging in.
     * 
     * @param Request $request The HTTP request.
     * @param TokenInterface $token The security token.
     * @param string $firewallName The firewall name.
     * @return Response|null Redirect response on success.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirect the user to the homepage upon successful login
        return new RedirectResponse($this->router->generate('app_home'));
    }

    /**
     * Checks if this authenticator supports the given user class.
     * 
     * @param string $class The user class.
     * @return bool True if supported, false otherwise.
     */
    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }

    /**
     * Generates the login URL.
     * 
     * @param Request $request The HTTP request.
     * @return string The login page URL.
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
