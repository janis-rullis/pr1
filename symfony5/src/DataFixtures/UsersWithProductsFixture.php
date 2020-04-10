<?php
namespace App\DataFixtures;

/**
 * #43 Fill test tables, before executing tests, using `./test.sh`.`. See `UserWihProductsGenerator`.
 */
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use \App\User\UserWihProductsGenerator;

class UsersWithProductsFixture extends Fixture implements FixtureGroupInterface
{

	private $userWithProductsGenerator;

	public static function getGroups(): array
	{
		return ['regular', 'users', 'users_with_products'];
	}

	public function __construct(UserWihProductsGenerator $userWithProductsGenerator)
	{
		$this->userWithProductsGenerator = $userWithProductsGenerator;
	}

	public function load(ObjectManager $manager)
	{
		$this->userWithProductsGenerator->generate(10);
	}
}
