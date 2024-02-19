<?php

namespace App\Service;

use App\Entity\Users;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class TokenAuthenticator {
    private $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager){
        $this->jwtManager = $jwtManager;
    }

    public function createToken(Users $user) {
        $payload = [
            'sub' => $user->getName(),
            'exp' => time() + 3600
        ];

        return $this->jwtManager->create($payload);
    }
}