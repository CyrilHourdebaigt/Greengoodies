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

        // On récupère le contenu JSON envoyé dans le body de la requête
        $data = json_decode($request->getContent(), true);

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        // Si l'un des deux champs est absent, la requête est invalide
        if (!$username || !$password) {
            return new JsonResponse([
                'message' => 'Identifiants incorrects.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

         // On recherche l'utilisateur en base grâce à son email
        $user = $userRepository->findOneBy([
            'email' => $username,
        ]);

        // Si l'utilisateur n'existe pas ou si le mot de passe est incorrect, on renvoie une erreur 401
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse([
                'message' => 'Identifiants incorrects.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // On s'assure que l'utilisateur à activé son accès à l'API
        if (!$user->isApiAccessEnabled()) {
            return new JsonResponse([
                'message' => 'Accès API non activé.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        // Si tout est bon, on génère un token JWT
        return new JsonResponse([
            'token' => $jwtManager->create($user),
        ], JsonResponse::HTTP_OK);
    }
}
