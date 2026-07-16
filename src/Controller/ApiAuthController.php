<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ApiAuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$password) {
            return new JsonResponse([
                'message' => 'Identifiants incorrects.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->findOneBy([
            'email' => $username,
        ]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse([
                'message' => 'Identifiants incorrects.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!$user->isApiAccessEnabled()) {
            return new JsonResponse([
                'message' => 'Accès API non activé.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'token' => $jwtManager->create($user),
        ], JsonResponse::HTTP_OK);
    }
}
