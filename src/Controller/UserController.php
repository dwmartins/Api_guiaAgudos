<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use App\Service\AwsUploader;
use App\Service\ValidatorFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\EnvVarLoaderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController{

    #[Route('/user/create', name: 'user_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, UsersRepository $userRepository): JsonResponse {
        try {
            $jsonData = json_decode($request->getContent());

            $userExists = $userRepository->findByEmail($jsonData->email);

            if($userExists) {
                $return['alert'] = 'Este e-mail já está em uso';
                return $this->json(
                    $return
                );
            }

            $user = new Users($jsonData);
            $user->setActive('Y');
            $user->setUserType('common');
            
            $hashedPassword = password_hash($jsonData->password, PASSWORD_DEFAULT);
            $user->setPassword($hashedPassword);
            $user->setToken($this->newCrypto());
            $user->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo')));
            $user->setUpdatedAt(new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo')));

            $em->persist($user);
            $em->flush();
            $userId = $user->getId();

            return $this->json([
                'userId' => $userId
            ]);

        } catch (\Exception $e) {
            return $this->json(['erro' => $e->getMessage()], 500);
        }
    }

    #[Route('/user/teste', methods: ['POST'])]
    public function teste(Request $request, AwsUploader $awsUploader, ValidatorFile $validatorFile) {
        try {
            $file = $request->files->get('photoUser');

            $fileValid = $validatorFile->validImage($file);

            if(isset($fileValid['invalid'])) {

            }

            $fileName = '1_Douglas';
            // $response =  $awsUploader->uploadPhotoUser($file, $fileName);

            return $this->json([
                'response' => $fileName
            ]);

        } catch (\Exception $e) {
            return $this->json(['erro' => $e->getMessage()], 500);
        }

    }

    public function newCrypto() {
        $bytes = random_bytes(32);
        return bin2hex($bytes);
    }
}