<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;

class UserFixtures extends Fixture
{
    public const ADMIN_USER = 'admin-user';
    public const USER1 = 'user1';
    public const USER2 = 'user2';

    public function load(ObjectManager $manager)
    {
        // Admin user
        $user = new User();
        $user->setName('udAL');
        $user->setRoles(array('ROLE_ADMIN'));
        $user->setApiToken('qiUCzxzEzz93tTbOqp9RREvOg7YE1I5uMatB8xNi');
        $manager->persist($user);
        $this->addReference(self::ADMIN_USER, $user);

        // Normal user 1
        $user = new User();
        $user->setName('user1');
        $user->setRoles(array('ROLE_USER'));
        $user->setApiToken('Qz325lCD0vf20vdnldpazOu5nYcp7V4mZ4eHpWdW');
        $manager->persist($user);
        $this->addReference(self::USER1, $user);

        // Normal user 2
        $user = new User();
        $user->setName('user2');
        $user->setRoles(array('ROLE_USER'));
        $user->setApiToken('xA2h8a6v0arMV48nuIw36loOpkGRoYIx6NWgIsfy');
        $manager->persist($user);
        $this->addReference(self::USER2, $user);

        $manager->flush();
    }
}
