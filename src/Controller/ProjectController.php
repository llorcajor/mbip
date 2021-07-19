<?php

namespace App\Controller;

use App\Entity\Category;
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
use Doctrine\ORM\EntityManager;


use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

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
                    $user = new User();
                    $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                        'id' => $user_id
                    ]);
                    $category = new Category();
                    $category = $this->getDoctrine()->getRepository(Category::class)->findOneBy([
                        'id' => 1
                    ]);



                    $project = new Project();
                    $project->setUser($user);
                    $project->setName($title);
                    $project->setDescription($description);
                    $project->setCategory($category);

                    $createdAt = new \DateTime('now');
                    $project->setCreatedAt($createdAt);




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

    public function projects(Request $request, JwtAuth $jwt_auth, PaginatorInterface $paginator)
    {
        $data = [
            'status' => 'error',
            'code' => 404,
            'message' => 'No se pueden listar los proyectos'
        ];
        // Reecoger la cabecera de autenticación
        $token = $request->headers->get('Authorization');

        // Comprobar token
        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {
            // Conseguir identidad usuario
            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();

            // Configurar el bundle de paginacion
            $dql = "SELECT v FROM App\Entity\Project v WHERE v.user = {$identity->sub} ORDER BY v.id DESC";
            $query = $em->createQuery($dql);





            // Hacer consulta paginacion
            $page = $request->query->getInt('page', 1);
            $items_per_page = 5;

            // recoger el parametro page de la url

            // Invocar paginacion
            $pagination = $paginator->paginate($query, $page, $items_per_page);
            $total = $pagination->getTotalItemCount();
            // Preparar array para enviar
            $data = [
                'status' => 'successs',
                'code' => 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_page' => ceil($total / $items_per_page),
                'projects' => $pagination,
                'user_id' => $identity->sub

            ];
        }



        return $this->resjson($data);
    }


    public function project(Request $request, JwtAuth $jwt_auth, $id = null)
    {
        $data = [
            'status' => 'error',
            'code' => 404,
            'message' => 'Video no encontrado',
            'id' => $id
        ];
        // Sacar token
        $token = $request->headers->get('Authorization');
        $authCheck = $jwt_auth->checkToken($token);
        if ($authCheck) {
            // Sacar identidad
            $identity = $jwt_auth->checkToken($token, true);
            // Sacar el proyecto
            $project = $this->getDoctrine()->getRepository(Project::class)->findOneBy([
                'id' => $id,
                'user' => $identity->sub
            ]);
            // Comprobar si el proyecto existe y es propiedad del usuario
            if ($project && is_object($project)) {
                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'project' => $project
                ];
            }
        }

        // Devolver una respuesta

        return $this->resjson($data);
    }
}
