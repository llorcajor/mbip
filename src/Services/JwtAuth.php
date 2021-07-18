<?php

namespace App\Services;

use Firebase\JWT\JWT;
use App\Entity\User;

class JwtAuth
{
    public $manager;
    public $key;

    public function __construct($manager)
    {
        $this->manager = $manager;

        $this->key = 'hola_que_tal1234567';
    }

    public function signup($email, $password, $gettoken = null)
    {
        // COMPORBAR SI EXISTE EL USUARIO
        $user = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'password' => $password
        ]);

        $signup = false;
        if (is_object($user)) {
            $signup = true;
        }
        // SI EXISTE, GENERAR TOKEN
        if ($signup) {
            $token = [
                'sub' => $user->getId(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'email' => $user->getEmail(),
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60)

            ];
            // COMPROBAR EL FLAG GETTOKEN
            $jwt = JWT::encode($token, $this->key, 'HS256');
            if (!empty($gettoken)) {

                $data = $jwt;
            } else {
                $decoded = JWT::decode($jwt, $this->key, ['HS256']);
                $data = $decoded;
            }
        } else {
            $data = [
                'status' => 'error',
                'message' => 'Login incorrecto'
            ];
        }


        // DEVOLVER DATOS

        return $data;
    }

    public function checkToken($jwt, $identity = false)
    {
        $auth = false;


        $decoded = JWT::decode($jwt, $this->key, ['HS256']);



        if ($decoded && !empty($decoded) && is_object($decoded) && isset($decoded->sub)) {
            $auth = true;
        } else {
            $auth = false;
        }

        if ($identity) {
            return $decoded;
        } else {
            return $auth;
        }





        return $auth;
    }
}
