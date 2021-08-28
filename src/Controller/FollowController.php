<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Follow;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use App\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

use Symfony\Component\Mime\Email;
// use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;
use App\Services\JwtAuth;
use Doctrine\ORM\Mapping\PostRemove;
use Doctrine\ORM\EntityManager;


use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

class FollowController extends AbstractController
{

    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/FollowController.php',
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

    public function onMatch(Request $request, JwtAuth $jwt_auth, $id = null)
    {
        $data = [
            'status' => 'error',
            'code' => 404,
            'message' => 'Error al dar match'
        ];

        // Recoger el token
        $token = $request->headers->get('Authorization', null);
        // Comprobar si es correcto
        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {

            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();

            if ($id != null) {
                $user_id = ($identity->sub != null) ? $identity->sub : null;
                $follow_created = $this->getDoctrine()->getRepository(Follow::class)->findOneBy([
                    'user' => $user_id,
                    'project' => $id
                ]);
                var_dump($follow_created);

                if (!$follow_created) {
                    $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                        'id' => $user_id
                    ]);

                    $project = $this->getDoctrine()->getRepository(Project::class)->findOneBy([
                        'id' => $id
                    ]);




                    $follow = new Follow();
                    $follow->setProject($project);
                    $follow->setUser($user);
                    $createdAt = new \DateTime('now');
                    $follow->setCreatedAt($createdAt);



                    $em->persist($follow);
                    $em->flush();

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Match creado'
                    ];
                }
            }

            $data = [
                'status' => 'error',
                'code' => 404,
                'message' => 'Error al dar match'
            ];
        }

        return $this->resjson($data);
    }

    public function checkMatch(Request $request, JwtAuth $jwt_auth, PaginatorInterface $paginator)
    {
        $data = [
            'status' => 'error',
            'code' => 404,
            'message' => 'Error al buscar los match'
        ];

        // Recoger el token
        $token = $request->headers->get('Authorization', null);
        // Comprobar si es correcto
        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {
            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();

            $dql = "SELECT v FROM App\Entity\Follow v WHERE v.user != {$identity->sub} ORDER BY v.id DESC";
            $query = $em->createQuery($dql);

            $pagination = $paginator->paginate($query, 1, 5);




            $data = [
                'status' => 'success',
                'code' => 200,
                'message' => 'Match traidos correctamente',
                'query' => $pagination
            ];
        }

        return $this->resjson($data);
    }

    public function checkMyMatchs(Request $request, JwtAuth $jwt_auth, PaginatorInterface $paginator)
    {
        $data = [
            'status' => 'error',
            'code' => 404,
            'message' => 'Error al buscar los match'
        ];

        // Recoger el token
        $token = $request->headers->get('Authorization', null);
        // Comprobar si es correcto
        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {
            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();

            $dql = "SELECT v FROM App\Entity\Follow v WHERE v.user = {$identity->sub} ORDER BY v.id DESC";
            $query = $em->createQuery($dql);

            $pagination = $paginator->paginate($query, 1, 5);


            $data = [
                'status' => 'success',
                'code' => 200,
                'message' => 'Match traidos correctamente',
                'query' => $pagination
            ];
        }

        return $this->resjson($data);
    }

    public function remove(Request $request, JwtAuth $jwt_auth, $id = null)
    {
        $data = [
            'status' => 'error',
            'code' => 404,
            'message' => 'Follow no encontrado'
        ];

        $token = $request->headers->get('Authorization');

        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {
            $identity = $jwt_auth->checkToken($token, true);

            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();
            $follow = $doctrine->getRepository(Follow::class)->findOneBy([
                'id' => $id
            ]);

            if ($follow && is_object($follow)) {
                $em->remove($follow);
                $em->flush();

                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Follow borrado'
                ];
            }
        }




        return $this->resjson($data);
    }

    public function sendMail(Request $request, JwtAuth $jwt_auth, $id = null, MailerInterface $mailer)
    {

        $data = [
            'status' => 'error',
            'code' => 404,
            'message' => 'Follow no encontrado'
        ];

        $token = $request->headers->get('Authorization');

        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {

            $identity = $jwt_auth->checkToken($token, true);

            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();
            $user = $doctrine->getRepository(User::class)->findOneBy([
                'id' => $id
            ]);


            $mail1 = $identity->email;

            $mail2 = $user->getEmail();

            $email = (new Email())
                ->from('jjlltt75@gmail.com')
                ->to($mail2)
                ->subject('Match realizado en MBIP!')
                ->text('Sending emails is fun again!')
                ->html('<p>El usuario</p>' . $mail1 . '<p>ha aceptado la solicitud por tu proyecto</p>');


            try {
                $mailer->send($email);
            } catch (TransportExceptionInterface $e) {
                // Error
            }

            $data = [
                'status' => 'success',
                'code' => 200,
                'message' => 'Email enviado'
            ];
        }


        return $this->resjson($data);
    }
}
