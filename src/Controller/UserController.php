<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use App\Service\AwsUploader;
use App\Service\ValidatorFile;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\Cast\Object_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\EnvVarLoaderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController{

    #[Route('/user/create', name: 'user_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, UsersRepository $userRepository, ValidatorFile $validatorFile, AwsUploader $awsUploader): JsonResponse {
        try {
            $jsonData = $request->request->all();
            $files = $request->files->all();
            $data = (object) $jsonData;
            $files = (object) $files;

            $userExists = $userRepository->findByEmail($data->email);

            if($files->photo) {
                $imgValid = $validatorFile->validImage($files->photo);

                if(isset($imgValid['invalid'])) {
                    $response = $imgValid['invalid'];
                    return $this->json([
                        'alert' => $response
                    ]);
                }
            }

            if($userExists) {
                $return['alert'] = 'Este e-mail já está em uso';
                return $this->json(
                    $return
                );
            }

            $user = new Users($data);
            $user->setActive('Y');
            $user->setUserType('common');
            
            $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);
            $user->setPassword($hashedPassword);
            $user->setToken($this->newCrypto());
            $user->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo')));
            $user->setUpdatedAt(new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo')));

            $em->persist($user);
            $em->flush();
            $userId = $user->getId();

            if($files->photo) {
                $fileType = $files->photo->getClientMimeType();
                $mime = explode('/' , $fileType);
                $fileName = $userId .'_'. $user->getName() .'.'. $mime[1];
                $user->setPhotoUrl($_ENV['URLDOCS']. '/' . $_ENV['FOLDERIMGUSERS']. '/' .$fileName);
                $em->flush();
                $awsUploader->uploadPhotoUser($files->photo, $userId.'_'.$user->getName());
            }

            return $this->json([
                'success' => 'Usuário criado com sucesso.'
            ], 201);

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