<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\UserFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;

class UserController extends AbstractFOSRestController
{
    private EntityManagerInterface $em;
    private UserRepository $repository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        UserRepository $repository,
        LoggerInterface $logger)
    {
        $this->em = $em;
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * @Rest\Get(path="/users", name="users")
     * @Rest\View(serializerGroups={"user_list"})
     */
    public function getUsersAction() : array
    {
        return $this->repository->findAll();
    }

    /**
     * @Rest\Get(path="/user/{id}", name="user.get")
     * @Rest\View(serializerGroups={"user_details"})
     */
    public function getUserAction($id): User
    {
        if($id) {
            $user = $this->repository->find($id);

            if($user) {
                return $user;
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
     * @Rest\Post(path="/user/new", name="user.new")
     * @Rest\View(serializerGroups={"user_details"})
     */
    public function postNewUserAction(Request $request) : User
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Not Allowed');
        $user = new User();
        return $this->postUser($request, $user);
    }

    /**
     * @Rest\Post(path="/user/{id}", name="user.post")
     * @Rest\View(serializerGroups={"user_details"})
     */
    public function postUserAction($id, Request $request) : User
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Not Allowed');
        $user = $this->repository->find($id);
        if($user) {
            return $this->postUser($request, $user);
        }
        else {
            throw new BadRequestHttpException('Bad Request');
        }
    }

    private function postUser(Request $request, User $user) : User
    {
        if($this->uniqueUsername($user, $request->request->get('name')))
        {
            $form = $this->createForm(UserFormType::class, $user);
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()) {
                $this->em->persist($user);
                $this->em->flush();
                return $user;
            }
            else {
                throw new BadRequestHttpException('Bad Request');
            }
        }
        else {
            throw new BadRequestHttpException('User name must be unique');
        }
    }

    /**
     * @Rest\Delete(path="/user/{id}", name="user.delete")
     */
    public function deleteUserAction($id, Request $request, Security $security) : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Not Allowed');
        $user = $this->repository->find($id);
        if($user) {
            if($user->getUsername() != $security->getUser()->getUsername()) {
                $this->em->remove($user);
                $this->em->flush();
                return new Response('', Response::HTTP_OK);
            }
            else {
                throw new BadRequestHttpException('Can\'t delete yourself');
            }
        }
        else {
            throw new BadRequestHttpException('Bad Request');
        }
    }

    private function uniqueUsername(User $user, String $name): bool
    {
        $users = $this->repository->findBy(['name' => $name]);
        return sizeof($users) == 0 || (
            sizeof($users) == 1 && $users[0]->getId() == $user->getId()
        );
    }
}