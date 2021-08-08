<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;
use App\Services\JwtAuth;

class UserController extends AbstractController
{
    private function resjson($data)
    {
        // Serializar datos con serializer
        $json = $this->get('serializer')->serialize($data, 'json');

        //Response con httpfoundation
        $response = new Response();


        //Asignar contenido a la respuesta
        $response->setContent($json);

        //Indicar formato de respuesta
        $response->headers->set('Content-Type', 'application/json');


        //Devolver la respuesta
        return $response;
    }


    public function index()
    {
        $user_repo = $this->getDoctrine()->getRepository(User::class);

        $users = $user_repo->findAll();

        return $this->json($users);
    }

    public function create(Request $request)
    {
        // Pick up data from post
        $json = $request->get('json', null);
        // Decode the json
        $params = json_decode($json);
        // Response by default
        $data = [
            'status' => 'error',
            'code' => 200,
            'message' => 'Error creating the user',
        ];

        // Check and validate data
        if ($json != null) {
            $name = (!empty($params->name)) ? $params->name : null;
            $surname = (!empty($params->surname)) ? $params->surname : null;
            $email = (!empty($params->email)) ? $params->email : null;
            $password = (!empty($params->password)) ? $params->password : null;
        }
        // If the data is correct, create the user object
        $user = new User();
        $user->setName($name);
        $user->setSurname($surname);
        $user->setEmail($email);
        $user->setRole('USER');
        $user->setCreatedAt(new \DateTime('now'));

        // Encode the password
        $pwd = hash('sha256', $password);
        $user->setPassword($pwd);

        $data = $user;
        // Check if the user already exist (duplicate)
        $em = $this->getDoctrine()->getManager();

        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $isset_user = $user_repo->findBy(array(
            'email' => $email
        ));

        // If it doesn't, store it in the bbdd
        if (count($isset_user) == 0) {
            $em->persist($user);
            $em->flush();


            $data = [
                'status' => 'succes',
                'code' => 200,
                'message' => 'The user has been created',
                'user' => $user
            ];
        } else {
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'The user already exist'
            ];
        }
        // Make response in json
        return $this->resjson($data);
    }


    public function login(Request $request, JwtAuth $jwt_auth)
    {
        // RECIBIR LOS DATOS POR POST
        $json = $request->get('json', null);
        $params = json_decode($json);
        // ARRAY POR DEFECTO
        $data = [
            'status' => 'error',
            'code' => 200,
            'message' => 'El usuario no se ha podido identificar'
        ];
        // COMPORBAAR Y VALIDAR DATOS
        if ($json != null) {
            $email = (!empty($params->email)) ? $params->email : null;
            $password = (!empty($params->password)) ? $params->password : null;
            $gettoken = (!empty($params->gettoken)) ? $params->gettoken : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
                new Email()
            ]);

            if (!empty($email) && !empty($password) && count($validate_email) == 0) {
                // CIFRAR CONTRASEÑA
                $pwd = hash('sha256', $password);
                // SI TODO OK, LLAMAREMOS A UN SERVICIO PARA IDENTIFICAR AL USUARIO (JWT, TOKEN O UN OBJETO)
                if ($gettoken) {
                    $signup = $jwt_auth->signup($email, $pwd, $gettoken);
                } else {
                    $signup = $jwt_auth->signup($email, $pwd);
                }

                return new JsonResponse($signup);
            }
        }

        // SI NOS DEVUELVE OK, RESPUESTA 

        return $this->resjson($data);
    }

    public function edit(Request $request, JwtAuth $jwt_auth)
    {
        // RECOGER LA CABECERA DE AUTENTICACIÓN
        $token = $request->headers->get('Authorization');
        // CREAR UN MÉTODO PARA COMPROBAR SI EL TOKEN ES CORRECTO
        $authCheck = $jwt_auth->checkToken($token);
        // RESPUESTA POR DEFECTO
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Usuario NO ACTUALIZADO'

        ];

        // SI ES CORRECTO, HACER LA ACTUALIZACIÓN DEL USUARIO
        if ($authCheck) {
            // ACTUALIZAR EL USUARIO

            // CONSEGUIR ENTITY MANAGER
            $em = $this->getDoctrine()->getManager();
            // CONSEGUIR DATOS USUARIO
            $identity = $jwt_auth->checkToken($token, true);

            // CONSEGUIR EL USUARIO A ACTUALIZAR COMPLET
            $user_repo = $this->getDoctrine()->getRepository(User::class);
            $user = $user_repo->findOneBy([
                'id' => $identity->sub
            ]);
            // RECOGER DATOS POR POST
            $json = $request->get('json');
            $params = json_decode($json);
            // COMPROBAR Y VALIDAR LOS DATOS
            if (!empty($json)) {
                $name = (!empty($params->name)) ? $params->name : null;
                $surname = (!empty($params->surname)) ? $params->surname : null;
                $email = (!empty($params->email)) ? $params->email : null;
                $image = (!empty($params->image)) ? $params->image : null;
                // ASIGNAR NUEVOS DATOS AL OBJETO DEL USUARIO
                $user->setEmail($email);
                $user->setName($name);
                $user->setSurname($surname);
                $user->setImage($image);
                // COMPROBAR DUPLICADOS
                $isset_user = $user_repo->findBy([
                    'email' => $email
                ]);

                if (count($isset_user) == 0 || $identity->email == $email) {
                    // GUARDAR CAMBIOS
                    $em->persist($user);
                    $em->flush();

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Usuario ACTUALIZADO',
                        'user' => $user
                    ];
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'No puedes usar ese email'
                    ];
                }
            }
        }


        return $this->resjson($data);
    }

    public function userDetail(Request $request, JwtAuth $jwt_auth, $id = null)
    {
        $data = [
            'status' => 'error',
            'code' => 404,
            'message' => 'Usuario no encontrado',
            'id' => $id
        ];
        // Sacar token
        $token = $request->headers->get('Authorization');
        $authCheck = $jwt_auth->checkToken($token);
        if ($authCheck) {

            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                'id' => $id
            ]);
            // Comprobar si el proyecto existe y es propiedad del usuario

            $data = [
                'status' => 'success',
                'code' => 200,
                'user' => $user
            ];
        }

        // Devolver una respuesta

        return $this->resjson($data);
    }
}
