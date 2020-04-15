<?php
namespace App\Tests\Order;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Exception\OrderShippingValidatorException;
use App\Interfaces\IOrderProductRepo;
use App\Interfaces\IOrderRepo;
use App\Interfaces\IProductRepo;
use App\Interfaces\IUserRepo;
use App\Service\Order\OrderProductCreator;
use App\Service\Order\OrderShippingService;
use App\Service\Order\OrderShippingValidator;
use App\Service\User\UserWihProductsGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * #38 Test that the order product data is stored in the database correctly.
 * Test v2 functionality: `vendor/bin/phpunit tests/`.
 */
class OrderUnitTest extends KernelTestCase
{

	private $c;
	private $entityManager;
	private $orderProductCreator;
	private $userWithProductsGenerator;
	private $orderShippingService;
	private $orderShippingValidator;
	private $orderRepo;
	private $userRepo;
	private $orderProductRepo;

	protected function setUp(): void
	{
		// #38 Using services in tests https://www.tomasvotruba.com/blog/2018/05/17/how-to-test-private-services-in-symfony/ https://symfony.com/doc/current/service_container.html
		// "However, if a service has been marked as private, you can still
		// alias it to access this service (via the alias)" `config/services.yaml` https://symfony.com/doc/current/service_container/alias_private.html#aliasing
		$kernel = self::bootKernel();
		$this->c = $kernel->getContainer();

		$this->orderProductCreator = $this->c->get('test.' . OrderProductCreator::class);
		$this->userWithProductsGenerator = $this->c->get('test.' . UserWihProductsGenerator::class);

		// #54 Maybe group this into an array.
		$this->orderRepo = $this->c->get('test.' . IOrderRepo::class);
		$this->userRepo = $this->c->get('test.' . IUserRepo::class);
		$this->productrRepo = $this->c->get('test.' . IProductRepo::class);
		$this->orderProductRepo = $this->c->get('test.' . IOrderProductRepo::class);
		$this->orderShippingService = $this->c->get('test.' . OrderShippingService::class);
		$this->orderShippingValidator = $this->c->get('test.' . OrderShippingValidator::class);

		// Using database in tests https://stackoverflow.com/a/52014145 https://symfony.com/doc/master/testing/database.html#functional-testing-of-a-doctrine-repository
		$this->entityManager = $this->c->get('doctrine')->getManager();
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		// doing this is recommended to avoid memory leaks
		$this->entityManager->close();
		$this->entityManager = null;
	}

	public function testOrderEnumExceptions()
	{
		$order = new Order();
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage("'aaa' " . \App\Helper\EnumType::INVALID_ENUM_VALUE);
		$this->expectExceptionCode(1);
		$order->setIsDomestic('aaa');
	}

	public function testOrderIsExpressExceptions()
	{
		$order = new Order();
		$this->expectException(OrderShippingValidatorException::class);
		$this->expectExceptionCode(1, '#40 Require the `is_domestic` to be set first');
		$order->setIsExpress('y');
	}

	public function testOrderIsExpressExceptions2()
	{
		$order = new Order();
		$this->expectException(OrderShippingValidatorException::class);
		$this->expectExceptionCode(2, '#40 Express must match the region.');
		$order->setIsDomestic('n');
		$order->setIsExpress('y');
	}

	public function testDrafAndCompletedtOrder()
	{
		$user = $this->userWithProductsGenerator->generate(1)[0];
		$this->assertNull($this->orderRepo->getCurrentDraft($user->getId()), '#36 #38 New customer shouldnt have a draft order.');
		$orderCreated = $this->orderRepo->insertIfNotExist($user->getId());
		$this->assertNull($orderCreated->getIsExpress());
		$this->assertNull($orderCreated->getShippingCost());
		$this->assertNull($orderCreated->getProductCost());
		$this->assertNull($orderCreated->getTotalCost());
		$this->assertEquals(Order::DRAFT, $orderCreated->getStatus(), "A created order should be a draft.");

		$this->orderRepo->markAsCompleted($orderCreated);

		$orderFound = $this->orderRepo->find($orderCreated->getId());

		$this->assertEquals(Order::COMPLETED, $orderFound->getStatus(), 'markAsCompleted() should change status to completed.');

		$orderCreated2 = $this->orderRepo->insertIfNotExist($user->getId());
		$this->assertNotEquals($orderCreated->getId(), $orderCreated2->getId(), '#40 A new order should be created after the previous is completed.');

		$orderFound2 = $this->orderRepo->getCurrentDraft($user->getId());
		$this->assertEquals($orderFound2->getId(), $orderCreated2->getId(), '#36 #38 Should find an existing one.');
		$this->assertEquals($orderFound2->getId(), $this->orderRepo->insertIfNotExist($user->getId())->getId(), '#36 #38 A new draft order should not be created if there is already one.');
	}

	/**
	 * #38 Test that the customer can add products to a cart (`order_product`).
	 */
	public function aatestAddProductsToCart()
	{
		// #40 Can't set this before the domestic is set and throw an execption there if they doesn't match.
		$draftOrder->setIsExpress('n');
		$this->entityManager->flush();
		$this->assertEquals(true, $this->orderProductRepo->markExpressShipping($draftOrder));
		$this->assertEquals(true, $this->orderProductRepo->setShippingRates($draftOrder));

		// #39 #33 #34 #37 Sum together costs from cart products and store in the order's costs.
		$this->assertEquals(true, $this->orderRepo->setOrderCostsFromCartItems($draftOrder));

		$shippingCostTotal = $validProductUpdated->getShippingCost() + $validProductUpdated2->getShippingCost() + $validProductUpdated3->getShippingCost();
		$productCostTotal = $validProductUpdated->getProductCost() + $validProductUpdated2->getProductCost() + $validProductUpdated3->getProductCost();
		$costTotal = $shippingCostTotal + $productCostTotal;
		$this->assertEquals($draftOrder->getShippingCost(), $shippingCostTotal);
		$this->assertEquals($draftOrder->getProductCost(), $productCostTotal);
		$this->assertEquals($draftOrder->getTotalCost(), $costTotal);
	}

	public function testToArray()
	{
		$user = $this->userWithProductsGenerator->generate(1)[0];
		$this->assertNull($this->orderRepo->getCurrentDraft($user->getId()), '#36 #38 New customer shouldnt have a draft order.');
		$orderCreated = $this->orderRepo->insertIfNotExist($user->getId());
		$this->orderProductCreator->handle($user->getId(), $user->getProducts()[0]->getId());
		$this->entityManager->clear();
		$orderFound = $this->orderRepo->getCurrentDraft($user->getId());

		$orderWithProducts = $this->orderRepo->mustFindUsersOrder($orderFound->getCustomerId(), $orderFound->getId())->toArray([], [Order::PRODUCTS]);
		$firstProduct = $orderWithProducts[Order::PRODUCTS][0];
		$this->assertEquals($orderFound->getId(), $firstProduct[OrderProduct::ORDER_ID]);
		$this->assertEquals($orderFound->getCustomerId(), $firstProduct[OrderProduct::CUSTOMER_ID]);

		$products = $orderFound->getProducts()->toArray()[0];
		foreach ($products as $product) {
			$this->assertEquals($orderFound->getId(), $product->getOrderId());
		}
	}
}
