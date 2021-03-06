<?php

namespace App\Service\User;

/*
 *  #53 Generate dummy users with products. Used in fixtures and tests.
 */
use App\Entity\Product;
use App\Interfaces\IProductRepo;
use App\Interfaces\IUserRepo;
use Doctrine\ORM\EntityManagerInterface;

class UserWihProductsGenerator
{
    private $userRepo;
    private $productRepo;
    private $em;

    /**
     * #53.
     */
    public function __construct(IUserRepo $userRepo, IProductRepo $productRepo, EntityManagerInterface $em)
    {
        $this->userRepo = $userRepo;
        $this->productRepo = $productRepo;
        $this->em = $em;
    }

    /**
     * #53 Generate dummy users with products. Used in fixtures and tests.
     *
     * @return type
     */
    public function generate(int $count = 1, int $balance = 10000)
    {
        $userIds = [];
        for ($i = 0; $i < $count; ++$i) {
            $user = $this->userRepo->generateDummyUser($i, $balance);
            $userIds[] = $user->getId();

            // #38 Create 1 mug and 1 shirt for each user.
            foreach (Product::PRODUCT_TYPES as $productType) {
                $this->productRepo->generateDummyUserProduct($user, $productType);
            }
        }
        $this->em->clear();

        return $this->userRepo->findById($userIds);
    }
}
