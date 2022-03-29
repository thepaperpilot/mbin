<?php declare(strict_types=1);

namespace App\Security;

use App\DTO\UserDto;
use App\Entity\Image;
use App\Entity\User;
use App\Repository\ImageRepository;
use App\Service\ImageManager;
use App\Service\UserManager;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\FacebookUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class FacebookAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private RouterInterface $router,
        private EntityManagerInterface $entityManager,
        private UserManager $userManager,
        private ImageManager $imageManager,
        private ImageRepository $imageRepository,
        private Slugger $slugger
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'oauth_facebook_verify';
    }

    public function authenticate(Request $request): Passport
    {
        $client  = $this->clientRegistry->getClient('facebook');
        $slugger = $this->slugger;

        $accessToken = $this->fetchAccessToken($client);

        try {
            $provider    = $client->getOAuth2Provider();
            $accessToken = $provider->getLongLivedAccessToken($accessToken->getToken());
        } catch (\Exception $e) {
        }

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client, $slugger) {
                /** @var FacebookUser $facebookUser */
                $facebookUser = $client->fetchUserFromToken($accessToken);

                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['oauthFacebookId' => $facebookUser->getId()]);

                if ($existingUser) {
                    return $existingUser;
                }

                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $facebookUser->getEmail()]);

                if ($user) {
                    $user->oauthFacebookId = $facebookUser->getId();
                } else {
                    $dto = (new UserDto())->create(
                        $slugger->slug($facebookUser->getName()).rand(1, 999),
                        $facebookUser->getEmail(),
                        $this->getAvatar($facebookUser->getPictureUrl())
                    );

                    $dto->plainPassword = bin2hex(random_bytes(20));

                    $user                  = $this->userManager->create($dto, false);
                    $user->oauthFacebookId = $facebookUser->getId();
                    $user->avatar          = $this->getAvatar($facebookUser->getPictureUrl());
                    $user->isVerified      = true;
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        $targetUrl = $this->router->generate('user_profile_edit');

        return new RedirectResponse($targetUrl);

    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    private function getAvatar(?string $pictureUrl): ?Image
    {
        if (!$pictureUrl) {
            return null;
        }

        try {
            $tempFile = $this->imageManager->download($pictureUrl);
        } catch (\Exception $e) {
            $tempFile = null;
        }

        if ($tempFile) {
            $image = $this->imageRepository->findOrCreateFromPath($tempFile);
            if ($image) {
                $this->entityManager->persist($image);
                $this->entityManager->flush();
            }
        }

        return $image ?? null;
    }
}
