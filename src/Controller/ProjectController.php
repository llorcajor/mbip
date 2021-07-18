<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use App\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;
use App\Services\JwtAuth;
use Doctrine\ORM\Mapping\PostRemove;

class ProjectController extends AbstractController
{

    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ProjectController.php',
        ]);
    }

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

    public function newProject(Request $request, JwtAuth $jwt_auth)
    {
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'El proyecto no ha podido crearse'
        ];


        // Recoger el token
        $token = $request->headers->get('Authorization', null);
        // Comprobar si es correcto
        $authCheck = $jwt_auth->checkToken($token);



        if ($authCheck) {
            // Recoger datos por post
            $json = $request->get('json', null);
            $params = json_decode($json);
            // Recoger el objeto del usuario identificado
            $identity = $jwt_auth->checkToken($token, true);

            // Comprobar y validar datos
            if (!empty($json)) {
                $user_id = ($identity->sub != null) ? $identity->sub : null;
                $title = (!empty($params->name)) ? $params->name : null;
                $description = (!empty($params->description)) ? $params->description : null;


                if (!empty($user_id) && !empty($title)) {
                    // Guardar en la bbdd

                    $em = $this->getDoctrine()->getManager();
                    $user = $this->getDoctrine()->getRepository(User::class)->findBy([
                        'id' => $user_id
                    ]);


                    $project = new Project();
                    $project->setUser($user);
                    $project->setName($title);
                    $project->setDescription($description);

                    $createdAt = new \DateTime('now');
                    $project->setCreatedAt($createdAt);
                    var_dump($project);
                    die();



                    $em->persist($project);
                    $em->flush();

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'El proyecto se ha guardado',
                        'proyecto' => $project
                    ];
                }
            }
        }


        // Devolver respuesta

        return $this->resjson($data);
    }
}
