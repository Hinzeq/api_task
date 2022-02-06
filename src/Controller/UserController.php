<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use App\Entity\User as UserEntity;

class UserController extends AbstractController
{

    public function getList(Request $request): Response
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

        if($request->query->get('status') !== NULL){
            if($request->query->get('status') == 1) {
                $status = [
                    'stat' => 'success',
                    'message' => 'Success! User was deleted'
                ];
            } elseif($request->query->get('status') == 0) {
                $status = [
                    'stat' => 'failure',
                    'message' => 'Error! User was not deleted'
                ];
            }
        } else {
            $status = [
                'stat' => 'skip',
                'message' => ''
            ];
        }
        
        return $this->render('user/index.html.twig', [
            'users' => $data,
            'status' => $status
        ]);
    }

    public function getAdd(): Response
    {
        return $this->render('user/add.html.twig');
    }

    public function addNewUser(Request $request): Response
    {
        if(($request->request->get('email') == NULL || $request->request->get('email') == '')
        || ($request->request->get('first_name') == NULL || $request->request->get('first_name') == '')
        || ($request->request->get('last_name') == NULL || $request->request->get('last_name') == '')
        || ($request->request->get('avatar') == NULL || $request->request->get('avatar') == '')){
            
            $status = [
                'stat' => 'failure',
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
                    'stat' => 'success',
                    'message' => 'Success! User was added'
                ];
            } catch(Exception $e) {
                $status = [
                    'stat' => 'failure',
                    'message' => 'Error! User was not added!'
                ];
            }
        }

        return $this->render('user/add.html.twig', [
            'status' => $status
        ]);
    }

    public function getUserEdit($id): Response
    {
        $user = $this->getDoctrine()->getRepository(UserEntity::class)->findOneBy([
            'id' => $id
        ]);

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'avatar' => $user->getAvatar()
        ];

        return $this->render('user/edit.html.twig', [
            'user' => $data
        ]);
    }

    public function editUser(Request $request, $id): Response
    {
        if(($request->request->get('email') == NULL || $request->request->get('email') == '')
        || ($request->request->get('first_name') == NULL || $request->request->get('first_name') == '')
        || ($request->request->get('last_name') == NULL || $request->request->get('last_name') == '')
        || ($request->request->get('avatar') == NULL || $request->request->get('avatar') == '')){
            
            $status = [
                'stat' => 'failure',
                'message' => 'All fields must contain data'
            ];
            $em = $this->getDoctrine()->getManager();
            $user = $em->getReference(UserEntity::class, (int) $id);
        } else {
            try{
                $em = $this->getDoctrine()->getManager();

                $user = $em->getReference(UserEntity::class, (int) $id);

                $user->setEmail( $request->request->get('email') );
                $user->setFirstName( $request->request->get('first_name') );
                $user->setLastName( $request->request->get('last_name') );
                $user->setAvatar( $request->request->get('avatar') );

                $em->persist($user);
                $em->flush();
    
                $status = [
                    'stat' => 'success',
                    'message' => 'Success! Edit'
                ];
            } catch(Exception $e) {
                $status = [
                    'stat' => 'failure',
                    'message' => 'Error! Edit'
                ];
            }
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'avatar' => $user->getAvatar()
        ];

        return $this->render('user/edit.html.twig', [
            'status' => $status,
            'user' => $data
        ]);
    }

    public function deleteUser($id): RedirectResponse
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $user = $em->getReference(UserEntity::class, (int) $id);

            $em->remove($user);
            $em->flush();
            $status = 1;
        } catch(Exception $e) {
            $status = 0;
        }

        return $this->redirectToRoute('user_list', ['status' => $status]);
    }
}
