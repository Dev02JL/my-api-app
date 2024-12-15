<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        throw new \Exception('This should not be reached.');
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$this->isPasswordValid($password)) {
            return new JsonResponse([
                'error' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'User created successfully'], Response::HTTP_CREATED);
    }

    private function isPasswordValid(?string $password): bool
    {
        return preg_match('/[A-Z]/', $password) &&
            preg_match('/[a-z]/', $password) &&
            preg_match('/\d/', $password);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function logout(): JsonResponse
    {
        // Envoi d'une réponse indiquant au client de supprimer le token
        return new JsonResponse([
            'message' => 'Déconnexion réussie. Veuillez supprimer le token côté client.'
        ], 200);
    }
}