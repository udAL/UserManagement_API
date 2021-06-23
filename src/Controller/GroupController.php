<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\User;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GroupController extends AbstractFOSRestController
{
    private EntityManagerInterface $em;
    private GroupRepository $repository;
    private UserRepository $user_repository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        GroupRepository $repository,
        UserRepository $user_repository,
        LoggerInterface $logger)
    {
        $this->em = $em;
        $this->repository = $repository;
        $this->user_repository = $user_repository;
        $this->logger = $logger;
    }

    /**
     * @Rest\Get(path="/groups", name="groups")
     * @Rest\View(serializerGroups={"group_list"})
     */
    public function getGroupsAction() : array
    {
        return $this->repository->findAll();
    }

    /**
     * @Rest\Get(path="/group/{id}", name="group.get")
     * @Rest\View(serializerGroups={"group_details","user_list"})
     */
    public function getGroupAction($id): Group
    {
        if($id) {
            $group = $this->repository->find($id);

            if($group) {
                return $group;
            }
            else {
                throw new NotFoundHttpException('Not Found');
            }
        }
        else {
            throw new BadRequestHttpException('Bad Request');
        }
    }

    /**
     * @Rest\Post(path="/group/new", name="group.new")
     * @Rest\View(serializerGroups={"group_details","user_list"})
     */
    public function postNewGroupAction(Request $request) : Group
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Not Allowed');
        $group = new Group();
        $this->em->persist($group);
        $this->em->flush();
        return $group;
    }

    /**
     * @Rest\Post(path="/group/{id}/add", name="group.add")
     * @Rest\RequestParam(name="users")
     * @Rest\View(serializerGroups={"group_details","user_list"})
     */
    public function addUserGroup($id, Request $request) : Group
    {
        return $this->modifyUserGroup($id, $request->request->get('users'), 'addUser');
    }

    /**
     * @Rest\Post(path="/group/{id}/remove", name="group.remove")
     * @Rest\RequestParam(name="users")
     * @Rest\View(serializerGroups={"group_details","user_list"})
     */
    public function removeUserGroup($id, Request $request) : Group
    {
        return $this->modifyUserGroup($id, $request->request->get('users'), 'removeUser');
    }

    private function modifyUserGroup($id, $id_users, $callback) : Group
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Not Allowed');
        if($id_users) {
            $group = $this->repository->find($id);
            if($group) {
                if(!is_array($id_users)) $id_users = array($id_users);
                foreach($id_users as $id_user) {
                    $user = $this->getUserById($id_user);
                    $group->$callback($user);
                }
                $this->em->persist($group);
                $this->em->flush();
                return $group;
            }
            else {
                throw new BadRequestHttpException('Bad Request');
            }
        }
        else {
            throw new BadRequestHttpException('Bad Request');
        }
    }

    private function getUserById($id_user) : User
    {
        $user = $this->user_repository->find($id_user);
        if($user) {
            return $user;
        }
        else {
            throw new BadRequestHttpException('Bad Request');
        }
    }

    /**
     * @Rest\Delete(path="/group/{id}", name="group.delete")
     */
    public function deleteGroupAction($id, Request $request) : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Not Allowed');
        $group = $this->repository->find($id);
        if($group) {
            if(sizeof($group->getUsers()) == 0) {
                $this->em->remove($group);
                $this->em->flush();
                return new Response('', Response::HTTP_OK);
            }
            else {
                throw new BadRequestHttpException('Can\'t delete a group with members');
            }
        }
        else {
            throw new BadRequestHttpException('Bad Request');
        }
    }
}