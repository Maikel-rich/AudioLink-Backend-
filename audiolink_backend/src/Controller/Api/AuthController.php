<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'Email y password son obligatorios'], 400);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setFullName($data['fullName'] ?? null);

        // 0: Productor, 1: Artista, 2: Admin
        $roleValue = isset($data['role']) ? (int)$data['role'] : User::ROLE_ARTIST;
        $user->setRole($roleValue);

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        if (isset($data['genres'])) $user->setGenres($data['genres']);
        if (isset($data['languages'])) $user->setLanguages($data['languages']);

        try {
            $entityManager->persist($user);
            $entityManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }

        return new JsonResponse([
            'message' => 'Usuario registrado con éxito',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole()
            ]
        ], 201);
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return new JsonResponse(['message' => 'Endpoint de login listo para implementar seguridad']);
    }
}
