<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use App\Entity\User as UserEntity;

class UserController extends AbstractController
{

    public function getList(): Response
    {
        $users = $this->getDoctrine()->getRepository(UserEntity::class)->findAll();

        $data = [];

        foreach($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'avatar' => $user->getAvatar()
            ];
        }

        return $this->render('user/index.html.twig', [
            'users' => $data
        ]);
    }

    public function getAdd(): Response
    {
        return $this->render('user/add.html.twig');
    }

    public function addNewUser(Request $request): Response
    {
        if(($request->request->get('email') == NULL || $request->request->get('email') == '')
        && ($request->request->get('first_name') == NULL || $request->request->get('first_name') == '')
        && ($request->request->get('last_name') == NULL || $request->request->get('last_name') == '')
        && ($request->request->get('avatar') == NULL || $request->request->get('avatar') == '')){
            
            $status = [
                'stat' => false,
                'message' => 'All fields must contain data'
            ];
        } else {
            try{
                $last_id = $this->getDoctrine()->getRepository(UserEntity::class)->findOneBy([], [
                    'id' => 'DESC'
                ], 1);

                $user = new UserEntity();

                $user->setId( (empty($last_id) || $last_id == NULL) ? 1 : (int)$last_id->getId() + 1 );
                $user->setEmail( $request->request->get('email') );
                $user->setFirstName( $request->request->get('first_name') );
                $user->setLastName( $request->request->get('last_name') );
                $user->setAvatar( $request->request->get('avatar') );
    
                $em = $this->getDoctrine()->getManager();

                $em->persist($user);
                $em->flush();
    
                $status = [
                    'stat' => true,
                    'message' => 'Success! User was added'
                ];
            } catch(Exception $e) {
                $status = [
                    'stat' => false,
                    'message' => 'Error! User was not added!'
                ];
            }
        }

        return $this->render('user/add.html.twig', [
            'status' => $status
        ]);
    }
}
