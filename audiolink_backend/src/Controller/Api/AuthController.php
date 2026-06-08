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

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => strtolower(trim($data['email']))]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'El correo electrónico ya se encuentra registrado en el sistema'], 400);
        }

        $user = new User();
        $user->setEmail(strtolower(trim($data['email'])));
        $user->setFullName($data['fullName'] ?? null);

        $roleValue = isset($data['role']) ? (int)$data['role'] : User::ROLE_ARTIST;
        $user->setRole($roleValue);

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $user->setGenres($data['genres'] ?? []);
        $user->setLanguages($data['languages'] ?? []);
        $user->setIsVerified(false);
        $user->setCreatedAt(new \DateTime());

        try {
            $entityManager->persist($user);
            $entityManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Error crítico al escribir en la base de datos central.',
                'details' => $e->getMessage()
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

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'No estás autenticado o token inválido'], 401);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'role' => $user->getRole()
        ], 200);
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return new JsonResponse([
            'error' => 'Error en el firewall central de Symfony.'
        ], 500);
    }
}
