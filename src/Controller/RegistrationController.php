<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager,
    Security $security): Response
    {
        // On crée une nouvelle instance de User
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        // On traite la requête afin de récupérer les données envoyées par l'utilisateur
        $form->handleRequest($request);

        // Si le formulaire a été soumis et que les données sont valides, on peut créer le compte utilisateur
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Par défaut, un nouvel utilisateur n'a pas accès à l'API
            $user->setIsApiAccessEnabled(false);

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            // Après l'inscription, l'utilisateur est automatiquement connecté
            $security->login($user, 'form_login', 'main');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
