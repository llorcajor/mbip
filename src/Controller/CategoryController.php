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

class CategoryController extends AbstractController
{

    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CategoryController.php',
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

    public function categories(Request $request, JwtAuth $jwt_auth,  PaginatorInterface $paginator)
    {
        $data = [
            'status' => 'error',
            'code' => 404,
            'message' => 'Categorias no encontradas'
        ];
        // Sacar token
        $token = $request->headers->get('Authorization');
        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {
            // Sacar el proyecto
            $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();
            $pagination = $paginator->paginate($categories, 1, 10);


            // Comprobar si el proyecto existe y es propiedad del usuario
            if ($pagination && is_object($pagination)) {
                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'categories' => $pagination
                ];
            }
        }

        // Devolver una respuesta

        return $this->resjson($data);
    }
}
