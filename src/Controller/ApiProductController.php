<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ApiProductController extends AbstractController
{
    #[Route('/api/products', name: 'api_products', methods: ['GET'])]
    public function index(ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {
        // On récupère donc l'utilisateur authentifié grâce au token envoyé dans la requête
        $user = $this->getUser();

        // On vérifie que l'utilisateur a toujours activé son accès API
        if (!$user || !$user->isApiAccessEnabled()) {
            return new JsonResponse([
                'message' => 'Accès API non activé.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        // On récupère tous les produits présents en base
        $products = $productRepository->findAll();

        // On transforme les produits en JSON avec le groupe de sérialisation product:read
        $jsonProducts = $serializer->serialize($products, 'json', [
            'groups' => ['product:read'],
        ]);

        return new JsonResponse($jsonProducts, JsonResponse::HTTP_OK, [], true);
    }
}
