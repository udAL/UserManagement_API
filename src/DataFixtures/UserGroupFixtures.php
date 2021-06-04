<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserGroupFixtures extends Fixture
{
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            GroupFixtures::class,
        ];
    }

    public function load(ObjectManager $manager)
    {
        // Group 1
        $group = $this->getReference(GroupFixtures::GROUP1);
        $group->addUser($this->getReference(UserFixtures::ADMIN_USER));
        $group->addUser($this->getReference(UserFixtures::USER1));
        $manager->persist($group);

        // Group 2
        $group = $this->getReference(GroupFixtures::GROUP2);
        $group->addUser($this->getReference(UserFixtures::USER1));
        $manager->persist($group);

        $manager->flush();
    }
}
