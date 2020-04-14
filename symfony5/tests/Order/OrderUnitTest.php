<?php

namespace App\Tests\Order;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Exception\OrderShippingValidatorException;
use App\Exception\ProductIdValidatorException;
use App\Exception\UidValidatorException;
use App\Interfaces\IOrderProductRepo;
use App\Interfaces\IOrderRepo;
use App\Interfaces\IProductRepo;
use App\Interfaces\IUserRepo;
use App\Order\OrderProductCreator;
use App\Order\OrderShippingService;
use App\Order\OrderShippingValidator;
use App\User\UserWihProductsGenerator;
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
    private $impossibleInt = 3147483648;

    protected function setUp(): void
    {
        // #38 Using services in tests https://www.tomasvotruba.com/blog/2018/05/17/how-to-test-private-services-in-symfony/ https://symfony.com/doc/current/service_container.html
        // "However, if a service has been marked as private, you can still
        // alias it to access this service (via the alias)" `config/services.yaml` https://symfony.com/doc/current/service_container/alias_private.html#aliasing
        $kernel = self::bootKernel();
        $this->c = $kernel->getContainer();

        $this->orderProductCreator = $this->c->get('test.'.OrderProductCreator::class);
        $this->userWithProductsGenerator = $this->c->get('test.'.UserWihProductsGenerator::class);

        // #54 Maybe group this into an array.
        $this->orderRepo = $this->c->get('test.'.IOrderRepo::class);
        $this->userRepo = $this->c->get('test.'.IUserRepo::class);
        $this->productrRepo = $this->c->get('test.'.IProductRepo::class);
        $this->orderProductRepo = $this->c->get('test.'.IOrderProductRepo::class);
        $this->orderShippingService = $this->c->get('test.'.OrderShippingService::class);
        $this->orderShippingValidator = $this->c->get('test.'.OrderShippingValidator::class);

        // Using database in tests https://stackoverflow.com/a/52014145 https://symfony.com/doc/master/testing/database.html#functional-testing-of-a-doctrine-repository
        $this->entityManager = $this->c->get('doctrine')->getManager();
    }

    /**
     *  #40.
     */
    public function testOrderShippingExceptions()
    {
        $order = new Order();
        $this->expectException(OrderShippingValidatorException::class);
        $this->expectExceptionCode(1);
        $this->orderShippingService->set(1, []);
    }

    /**
     *  #40.
     */
    public function testOrderShippingExceptions2()
    {
        $order = new Order();
        $this->expectException(UidValidatorException::class);
        $this->expectExceptionCode(1);
        $this->orderShippingService->set(0, Order::VALID_SHIPPING_EXAMPLE);
    }

    /**
     * #40.
     */
    public function testOrderAddressValidatorExceptions()
    {
        $this->expectException(OrderShippingValidatorException::class);
        $this->expectExceptionCode(2);
        $this->orderShippingValidator->validateAddress([]);
    }

    /**
     * #40.
     */
    public function testOrderValidatorExceptions()
    {
        $this->expectException(OrderShippingValidatorException::class);
        $this->expectExceptionCode(1);
        $this->orderShippingValidator->validate([]);
    }

    /**
     * #40.
     */
    public function testOrderValidation()
    {
        $ship_to_address = Order::VALID_SHIPPING_EXAMPLE;
        unset($ship_to_address[Order::IS_EXPRESS]);
        $this->assertFalse($this->orderShippingValidator->hasRequiredKeys($ship_to_address));
        $this->assertEquals([Order::IS_EXPRESS => Order::IS_EXPRESS], $this->orderShippingValidator->getMissingKeys($ship_to_address));
        $ship_to_address[Order::IS_EXPRESS] = true;
        $this->assertTrue($this->orderShippingValidator->hasRequiredKeys($ship_to_address));

        $this->assertTrue($this->orderShippingValidator->isAddressValid($ship_to_address));
        $this->assertTrue($this->orderShippingValidator->isExpressShippingAllowed($ship_to_address));
        $this->assertTrue($this->orderShippingValidator->isValid($ship_to_address));
        $ship_to_address[Order::COUNTRY] = 'Latvia';
        $this->assertTrue($this->orderShippingValidator->isAddressValid($ship_to_address));
        $this->assertFalse($this->orderShippingValidator->isExpressShippingAllowed($ship_to_address));
        $this->assertFalse($this->orderShippingValidator->isValid($ship_to_address));
    }

    public function testOrderEnumExceptions()
    {
        $order = new Order();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("'aaa' ".\App\Helper\EnumType::INVALID_ENUM_VALUE);
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

    /**
     * #40.
     */
    public function testOrderProductExceptions()
    {
        $orderProduct = new OrderProduct();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("'aaa' ".\App\Helper\EnumType::INVALID_ENUM_VALUE);
        $orderProduct->setIsExpress('aaa');
    }

    /**
     * #40 Invalid params.
     */
    public function testOrderProductCreatorExceptions()
    {
        $orderProduct = new OrderProduct();
        $this->expectException(UidValidatorException::class);
        $this->expectExceptionCode(1);
        $this->orderProductCreator->handle($this->impossibleInt, $this->impossibleInt);
    }

    /**
     * #40 Invalid user, valid product.
     */
    public function testOrderProductCreatorExceptions1()
    {
        $user = $this->userRepo->getUserWithProducts();

        $this->expectException(UidValidatorException::class);
        $this->expectExceptionCode(1);
        $this->orderProductCreator->handle($this->impossibleInt, $user->getProducts()[0]->getId());
    }

    /**
     * #40 Invalid product, valid user.
     */
    public function testOrderProductCreatorExceptions2()
    {
        $user = $this->userRepo->getUserWithProducts();
        $this->expectException(ProductIdValidatorException::class);
        $this->expectExceptionCode(1);
        $this->orderProductCreator->handle($user->getId(), $this->impossibleInt);
    }

    /**
     * #38 Test that the customer can add products to a cart (`order_product`).
     */
    public function testAddProductsToCart()
    {
        $users = $this->userWithProductsGenerator->generate(3);

        // #38 #36 Create and get customer's draft order.
        // TODO: This probably should be moved to a separate test file.
        $this->assertNull($this->orderRepo->getCurrentDraft($users[2]->getId()), '#36 #38 New customer shouldnt have a draft order.');
        $draftOrder0 = $this->orderRepo->insertIfNotExist($users[2]->getId());

        // #40 Check status.
        $this->assertEquals(Order::DRAFT, $draftOrder0->getStatus());
        $this->orderRepo->markAsCompleted($draftOrder0);
        $draftOrder0 = $this->orderRepo->find($draftOrder0->getId());
        $this->assertEquals(Order::COMPLETED, $draftOrder0->getStatus());
        $draftOrder = $this->orderRepo->insertIfNotExist($users[2]->getId());
        $this->assertNotEquals($draftOrder0->getId(), $draftOrder->getId(), '#40 A new order should be created after the previous is completed.');

        $this->assertNotNull($draftOrder, '#36 #38 A draft order should be created if it doesnt exist.');
        $this->assertEquals($this->orderRepo->getCurrentDraft($users[2]->getId())->getId(), $draftOrder->getId(), '#36 #38 Should find an existing one.');
        $this->assertEquals($this->orderRepo->getCurrentDraft($users[2]->getId())->getId(), $this->orderRepo->insertIfNotExist($users[2]->getId())->getId(), '#36 #38 A new draft order shouldnt be created if there is already one.');

        $customerId = $users[2]->getId();
        $productId = $users[1]->getProducts()[0]->getId();

        // #39 #33 #34 TODO: MAybe this should be optimized.
        $validProduct = $this->orderProductCreator->handle($customerId, $productId);
        $this->assertEquals($validProduct->getCustomerId(), $customerId);
        $this->assertEquals($validProduct->getSellerId(), $users[1]->getId());
        $this->assertEquals($validProduct->getSellerTitle(), $users[1]->getName().' '.$users[1]->getSurname());
        $this->assertEquals($validProduct->getProductId(), $productId);
        $this->assertEquals($validProduct->getProductTitle(), $users[1]->getProducts()[0]->getTitle());
        $this->assertEquals($validProduct->getProductCost(), $users[1]->getProducts()[0]->getCost());
        $this->assertEquals($validProduct->getProductType(), $users[1]->getProducts()[0]->getType());
        $this->assertTrue($validProduct->getId() > 0);
        $this->assertEquals($validProduct->getOrderId(), $draftOrder->getId());
        $this->assertEquals($validProduct->getIsAdditional(), null);

        // #39 #33 #34 Add additional products  (ex., 2 pieces of the same t-shirt, 2nd is additional).
        $validProduct2 = $this->orderProductCreator->handle($customerId, $productId);
        $this->assertEquals($validProduct2->getCustomerId(), $customerId);
        $this->assertEquals($validProduct2->getSellerId(), $users[1]->getId());
        $this->assertEquals($validProduct2->getSellerTitle(), $users[1]->getName().' '.$users[1]->getSurname());
        $this->assertEquals($validProduct2->getProductId(), $productId);
        $this->assertEquals($validProduct2->getProductTitle(), $users[1]->getProducts()[0]->getTitle());
        $this->assertEquals($validProduct2->getProductCost(), $users[1]->getProducts()[0]->getCost());
        $this->assertEquals($validProduct2->getProductType(), $users[1]->getProducts()[0]->getType());
        $this->assertTrue($validProduct2->getId() > 0);
        $this->assertEquals($validProduct2->getOrderId(), $draftOrder->getId());
        $this->assertNotEquals($validProduct->getId(), $validProduct2->getId());
        $this->assertEquals($validProduct2->getIsAdditional(), null);

        $validProduct3 = $this->orderProductCreator->handle($customerId, $productId);
        $this->assertEquals($validProduct3->getCustomerId(), $customerId);
        $this->assertEquals($validProduct3->getSellerId(), $users[1]->getId());
        $this->assertEquals($validProduct3->getSellerTitle(), $users[1]->getName().' '.$users[1]->getSurname());
        $this->assertEquals($validProduct3->getProductId(), $productId);
        $this->assertEquals($validProduct3->getProductTitle(), $users[1]->getProducts()[0]->getTitle());
        $this->assertEquals($validProduct3->getProductCost(), $users[1]->getProducts()[0]->getCost());
        $this->assertEquals($validProduct3->getProductType(), $users[1]->getProducts()[0]->getType());
        $this->assertTrue($validProduct3->getId() > 0);
        $this->assertEquals($validProduct3->getOrderId(), $draftOrder->getId());
        $this->assertNotEquals($validProduct2->getId(), $validProduct3->getId());
        $this->assertEquals($validProduct3->getIsAdditional(), null);

        // #39 #33 #34 Mark additional products (ex., 2 pieces of the same t-shirt, 2nd is additional).
        $this->assertTrue($this->orderProductRepo->makrCartsAdditionalProducts($draftOrder));

        // #39 #33 #34 Collect updated items.
        $validProductUpdated = $this->orderProductRepo->find($validProduct->getId());
        $validProductUpdated2 = $this->orderProductRepo->find($validProduct2->getId());
        $validProductUpdated3 = $this->orderProductRepo->find($validProduct3->getId());

        // #39 #33 #34 Make sure they are marked correctly
        // TODO: Move all assertEquals() values to left side - that's the comparison side.
        $this->assertEquals($validProductUpdated->getIsAdditional(), 'n');
        $this->assertEquals($validProductUpdated2->getIsAdditional(), 'y');
        $this->assertEquals($validProductUpdated3->getIsAdditional(), 'y');

        // #39 #33 #34 Mark the order as domestic or international.
        $this->assertEquals($validProductUpdated->getIsDomestic(), null);
        $this->assertEquals($validProductUpdated2->getIsDomestic(), null);
        $this->assertEquals($validProductUpdated3->getIsDomestic(), null);

        // #39 #33 #34 TODO: Add here the value collection from the address parser.
        $this->assertEquals(null, $draftOrder->getIsDomestic());
        $draftOrder->setIsDomestic('y');
        $this->entityManager->flush();

        $this->assertEquals(true, $this->orderProductRepo->markDomesticShipping($draftOrder));
        $this->assertEquals($validProductUpdated->getOrderId(), $draftOrder->getId());

        // #39 #33 #34 Collect updated items.
        $validProductUpdated = $this->orderProductRepo->find($validProductUpdated->getId());
        $validProductUpdated2 = $this->orderProductRepo->find($validProductUpdated2->getId());
        $validProductUpdated3 = $this->orderProductRepo->find($validProductUpdated3->getId());

        // #39 #33 #34 Make sure they are marked correctly
        $this->assertEquals($validProductUpdated->getIsDomestic(), 'y');
        $this->assertEquals($validProductUpdated2->getIsDomestic(), 'y');
        $this->assertEquals($validProductUpdated3->getIsDomestic(), 'y');

        $draftOrder->setIsDomestic('n');
        $this->entityManager->flush();

        $this->assertEquals(true, $this->orderProductRepo->markDomesticShipping($draftOrder));

        // #39 #33 #34 Collect updated items.
        $validProductUpdated = $this->orderProductRepo->find($validProductUpdated->getId());
        $validProductUpdated2 = $this->orderProductRepo->find($validProductUpdated2->getId());
        $validProductUpdated3 = $this->orderProductRepo->find($validProductUpdated3->getId());

        // #39 #33 #34 Make sure they are marked correctly
        $this->assertEquals($validProductUpdated->getIsDomestic(), 'n');
        $this->assertEquals($validProductUpdated2->getIsDomestic(), 'n');
        $this->assertEquals($validProductUpdated3->getIsDomestic(), 'n');

        // #39 #33 #34 Mark order's shipping as express or standard.
        $this->assertEquals(null, $draftOrder->getIsExpress());
        $this->assertEquals($validProductUpdated->getIsExpress(), null);
        $this->assertEquals($validProductUpdated2->getIsExpress(), null);
        $this->assertEquals($validProductUpdated3->getIsExpress(), null);

        // #39 #33 #34 #37 Set order's product shipping costs based on the matching rates in the `v2_shipping_rates` table.
        $this->assertEquals($validProductUpdated->getShippingCost(), null);
        $this->assertEquals($validProductUpdated2->getShippingCost(), null);
        $this->assertEquals($validProductUpdated3->getShippingCost(), null);

        $this->assertEquals($draftOrder->getShippingCost(), null);
        $this->assertEquals($draftOrder->getProductCost(), null);
        $this->assertEquals($draftOrder->getTotalCost(), null);

        // #40 Can't set this before the domestic is set and throw an execption there if they doesn't match.
        $draftOrder->setIsExpress('n');
        $this->entityManager->flush();
        $this->assertEquals(true, $this->orderProductRepo->markExpressShipping($draftOrder));
        $this->assertEquals(true, $this->orderProductRepo->setShippingRates($draftOrder));

        // #39 #33 #34 #37 Sum together costs from cart products and store in the order's costs.
        $this->assertEquals(true, $this->orderRepo->setOrderCostsFromCartItems($draftOrder));

        // #39 #33 #34 Collect updated items.
        $draftOrder = $this->orderRepo->find($draftOrder->getId());
        $validProductUpdated = $this->orderProductRepo->find($validProductUpdated->getId());
        $validProductUpdated2 = $this->orderProductRepo->find($validProductUpdated2->getId());
        $validProductUpdated3 = $this->orderProductRepo->find($validProductUpdated3->getId());

        // #39 #33 #34 #37 T-shirt / International / Standard / First = 3$.
        $this->assertEquals(300, $validProductUpdated->getShippingCost());
        // #39 #33 #34 #37 T-shirt / International / Standard / Additional = 1.5$.
        $this->assertEquals(150, $validProductUpdated2->getShippingCost());
        // #39 #33 #34 #37 T-shirt / International / Standard / Additional = 1.5$.
        $this->assertEquals(150, $validProductUpdated3->getShippingCost());

        $shippingCostTotal = $validProductUpdated->getShippingCost() + $validProductUpdated2->getShippingCost() + $validProductUpdated3->getShippingCost();
        $productCostTotal = $validProductUpdated->getProductCost() + $validProductUpdated2->getProductCost() + $validProductUpdated3->getProductCost();
        $costTotal = $shippingCostTotal + $productCostTotal;
        $this->assertEquals($draftOrder->getShippingCost(), $shippingCostTotal);
        $this->assertEquals($draftOrder->getProductCost(), $productCostTotal);
        $this->assertEquals($draftOrder->getTotalCost(), $costTotal);

        $draftOrder->setIsDomestic('y');
        $this->entityManager->flush();
        $this->assertEquals(true, $this->orderProductRepo->markDomesticShipping($draftOrder));
        $draftOrder->setIsExpress('n');
        $this->entityManager->flush();
        $this->assertEquals(true, $this->orderProductRepo->markExpressShipping($draftOrder));

        // #39 #33 #34 Collect updated items.
        $validProductUpdated = $this->orderProductRepo->find($validProductUpdated->getId());
        $validProductUpdated2 = $this->orderProductRepo->find($validProductUpdated2->getId());
        $validProductUpdated3 = $this->orderProductRepo->find($validProductUpdated3->getId());

        $this->assertEquals('n', $draftOrder->getIsExpress());
        $this->assertEquals($validProductUpdated->getIsExpress(), 'n');
        $this->assertEquals($validProductUpdated2->getIsExpress(), 'n');
        $this->assertEquals($validProductUpdated3->getIsExpress(), 'n');

        $this->assertEquals(true, $this->orderProductRepo->setShippingRates($draftOrder));

        // #39 #33 #34 #37 Sum together costs from cart products and store in the order's costs.
        $this->assertEquals(true, $this->orderRepo->setOrderCostsFromCartItems($draftOrder));

        // #39 #33 #34 Collect updated items.
        $draftOrder = $this->orderRepo->find($draftOrder->getId());
        $validProductUpdated = $this->orderProductRepo->find($validProductUpdated->getId());
        $validProductUpdated2 = $this->orderProductRepo->find($validProductUpdated2->getId());
        $validProductUpdated3 = $this->orderProductRepo->find($validProductUpdated3->getId());

        // #39 #33 #34 #37 T-shirt / US / Standard / First = 1$.
        $this->assertEquals(100, $validProductUpdated->getShippingCost());
        // #39 #33 #34 #37 T-shirt / US / Standard / Additional = 0.5$.
        $this->assertEquals(50, $validProductUpdated2->getShippingCost());
        // #39 #33 #34 #37 T-shirt / US / Standard / Additional = 0.5$.
        $this->assertEquals(50, $validProductUpdated3->getShippingCost());

        $shippingCostTotal = $validProductUpdated->getShippingCost() + $validProductUpdated2->getShippingCost() + $validProductUpdated3->getShippingCost();
        $productCostTotal = $validProductUpdated->getProductCost() + $validProductUpdated2->getProductCost() + $validProductUpdated3->getProductCost();
        $costTotal = $shippingCostTotal + $productCostTotal;
        $this->assertEquals($draftOrder->getShippingCost(), $shippingCostTotal);
        $this->assertEquals($draftOrder->getProductCost(), $productCostTotal);
        $this->assertEquals($draftOrder->getTotalCost(), $costTotal);

        $draftOrder->setIsExpress('y');
        $this->entityManager->flush();
        $this->assertEquals(true, $this->orderProductRepo->markExpressShipping($draftOrder));

        // #39 #33 #34 Collect updated items.
        $validProductUpdated = $this->orderProductRepo->find($validProductUpdated->getId());
        $validProductUpdated2 = $this->orderProductRepo->find($validProductUpdated2->getId());
        $validProductUpdated3 = $this->orderProductRepo->find($validProductUpdated3->getId());

        $this->assertEquals('y', $draftOrder->getIsExpress());
        $this->assertEquals($validProductUpdated->getIsExpress(), 'y');
        $this->assertEquals($validProductUpdated2->getIsExpress(), 'y');
        $this->assertEquals($validProductUpdated3->getIsExpress(), 'y');

        $this->assertEquals(true, $this->orderProductRepo->setShippingRates($draftOrder));

        // #39 #33 #34 #37 Sum together costs from cart products and store in the order's costs.
        $this->assertEquals(true, $this->orderRepo->setOrderCostsFromCartItems($draftOrder));

        // #39 #33 #34 Collect updated items.
        $draftOrder = $this->orderRepo->find($draftOrder->getId());
        $validProductUpdated = $this->orderProductRepo->find($validProductUpdated->getId());
        $validProductUpdated2 = $this->orderProductRepo->find($validProductUpdated2->getId());
        $validProductUpdated3 = $this->orderProductRepo->find($validProductUpdated3->getId());

        // #39 #33 #34 #37 Check that shipping prices are correct.
        // #39 #33 #34 #37 T-shirt / US / Express  / First = 10$.
        $this->assertEquals(1000, $validProductUpdated->getShippingCost());
        // #39 #33 #34 #37 T-shirt / US / Express Additional = 10$.
        $this->assertEquals(1000, $validProductUpdated2->getShippingCost());
        // #39 #33 #34 #37 T-shirt / US / Express Additional = 10$.
        $this->assertEquals(1000, $validProductUpdated3->getShippingCost());

        $shippingCostTotal = $validProductUpdated->getShippingCost() + $validProductUpdated2->getShippingCost() + $validProductUpdated3->getShippingCost();
        $productCostTotal = $validProductUpdated->getProductCost() + $validProductUpdated2->getProductCost() + $validProductUpdated3->getProductCost();
        $costTotal = $shippingCostTotal + $productCostTotal;
        $this->assertEquals($draftOrder->getShippingCost(), $shippingCostTotal);
        $this->assertEquals($draftOrder->getProductCost(), $productCostTotal);
        $this->assertEquals($draftOrder->getTotalCost(), $costTotal);

        // #40 Validate that the shipping is set correctly.
        $this->assertEmpty($draftOrder->getName());
        $this->assertEmpty($draftOrder->getSurname());
        $this->assertEmpty($draftOrder->getStreet());
        $this->assertEmpty($draftOrder->getState());
        $this->assertEmpty($draftOrder->getZip());
        $this->assertEmpty($draftOrder->getCountry());
        $this->assertEmpty($draftOrder->getPhone());

        $ship_to_address = Order::VALID_SHIPPING_EXAMPLE;
        $draftOrder = $this->orderShippingService->set($draftOrder->getCustomerId(), $ship_to_address);
        $this->assertEquals($ship_to_address[Order::OWNER_NAME], $draftOrder->getName());
        $this->assertEquals($ship_to_address[Order::OWNER_SURNAME], $draftOrder->getSurname());
        $this->assertEquals($ship_to_address[Order::STREET], $draftOrder->getStreet());
        $this->assertEquals($ship_to_address[Order::STATE], $draftOrder->getState());
        $this->assertEquals($ship_to_address[Order::ZIP], $draftOrder->getZip());
        $this->assertEquals($ship_to_address[Order::COUNTRY], $draftOrder->getCountry());
        $this->assertEquals($ship_to_address[Order::PHONE], $draftOrder->getPhone());
        $this->assertEquals('y', $draftOrder->getIsDomestic());
        $this->assertEquals('y', $draftOrder->getIsExpress());

        //#40 Collect order's products.
        $orderWithProducts = $this->orderRepo->mustFindUsersOrder($draftOrder->getCustomerId(), $draftOrder->getId())->toArray([], [Order::PRODUCTS]);
        $firstProduct = $orderWithProducts[Order::PRODUCTS][0];
        $this->assertEquals($draftOrder->getId(), $firstProduct[OrderProduct::ORDER_ID]);
        $this->assertEquals($draftOrder->getCustomerId(), $firstProduct[OrderProduct::CUSTOMER_ID]);

        $products = $draftOrder->getProducts()->toArray()[0];
        foreach ($products as $product) {
            $this->assertEquals($draftOrder->getId(), $product->getOrderId());
        }
        // #44 TODO: Import huge datasets using fixtures and check how current queries will react on that.
        // #39 #33 #34 #37 TODO: Add `shipping_id` to `shipping_rates`.`id`.
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
