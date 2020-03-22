<?php
/**
 * #40 Doc Annotations https://symfony.com/doc/current/bundles/NelmioApiDocBundle/faq.html
 */
namespace App\Controller\v2;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use \App\v2\OrderShippingService;
use \App\v2\OrderService;
use \App\Exception\UidValidatorException;
use \App\Exception\OrderValidatorException;
use \App\Repository\v2\OrderRepository;
use \App\Entity\v2\Order;

class OrderController extends AbstractController
{

	/**
	 * #40 Set order's shipping.
	 * #40 TODO: Replace this with Order schema.
	 * 
	 * @Route("/users/v2/{customerId}/order/shipping", methods={"PUT"})
	 * @SWG\Tag(name="4. shipping")
	 * 
	 * @SWG\Parameter(
	 *   name="body",
	 *   in="body",
	 *   required=true,
	 *   @SWG\Schema(
	 *    required={"name", "surname", "street", "country", "phone", "is_express"},
	 *    @SWG\Property(property="name", type="string", example="John"),
	 *    @SWG\Property(property="surname", type="string", example="Doe"),
	 *    @SWG\Property(property="street", type="string", example="Palm street 25-7"),
	 *    @SWG\Property(property="state", type="string", example="California"),
	 *    @SWG\Property(property="zip", type="string", example="60744"),
	 *    @SWG\Property(property="country", type="string", example="US"),
	 *    @SWG\Property(property="phone", type="string", example="+1 123 123 123"),
	 *    @SWG\Property(property="is_express", type="boolean", example=true)
	 *   )
	 * )
	 * @SWG\Response(
	 *   response=200, description="Saved.",
	 *   @SWG\Schema(
	 *    @SWG\Property(property="id", type="integer", example=1),
	 *    @SWG\Property(property="is_domestic", type="string", example="y"),
	 *    @SWG\Property(property="is_express", type="string", example="y"),
	 *    @SWG\Property(property="shipping_cost", type="integer", example=1000),
	 *    @SWG\Property(property="product_cost", type="integer", example=1000),
	 *    @SWG\Property(property="total_cost", type="integer", example=2000),
	 *    @SWG\Property(property="name", type="string", example="John"),
	 *    @SWG\Property(property="surname", type="string", example="Doe"),
	 *    @SWG\Property(property="street", type="string", example="Palm street 25-7"),
	 *    @SWG\Property(property="state", type="string", example="California"),
	 *    @SWG\Property(property="zip", type="string", example="60744"),
	 *    @SWG\Property(property="country", type="string", example="US"),
	 *    @SWG\Property(property="phone", type="string", example="+1 123 123 123")
	 *   )
	 * )
	 * @SWG\Response(
	 *   response=404, description="Not found.",
	 *   @SWG\Schema(
	 *    @SWG\Property(property="id", type="string", example="invalid user"),
	 *   )
	 * )
	 * 
	 * @param Request $request
	 * @param OrderShippingService $orderShippingService
	 * @param int $customerId
	 * @return JsonResponse
	 */
	public function setShipping(Request $request, OrderShippingService $orderShippingService, int $customerId): JsonResponse
	{
		try {
			$resp = $orderShippingService->set($customerId, json_decode($request->getContent(), true))->toArray();
			return $this->json($resp, Response::HTTP_OK);
		} catch (UidValidatorException $e) {
			return $this->json($e->getErrors(), Response::HTTP_NOT_FOUND);
		} catch (\Exception $e) {
			return $this->json($e->getErrors(), Response::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * #40 Complete the order.
	 * #40 TODO: Replace this with Order schema.
	 * 
	 * @Route("/users/v2/{customerId}/order/complete", methods={"PUT"})
	 * @SWG\Tag(name="5. complete order")
	 * @SWG\Response(
	 *   response=200, description="Saved.",
	 *   @SWG\Schema(
	 *    @SWG\Property(property="id", type="integer", example=1),
	 *    @SWG\Property(property="is_domestic", type="string", example="y"),
	 *    @SWG\Property(property="is_express", type="string", example="y"),
	 *    @SWG\Property(property="shipping_cost", type="integer", example=1000),
	 *    @SWG\Property(property="product_cost", type="integer", example=1000),
	 *    @SWG\Property(property="total_cost", type="integer", example=2000),
	 *    @SWG\Property(property="name", type="string", example="John"),
	 *    @SWG\Property(property="surname", type="string", example="Doe"),
	 *    @SWG\Property(property="street", type="string", example="Palm street 25-7"),
	 *    @SWG\Property(property="state", type="string", example="California"),
	 *    @SWG\Property(property="zip", type="string", example="60744"),
	 *    @SWG\Property(property="country", type="string", example="US"),
	 *    @SWG\Property(property="phone", type="string", example="+1 123 123 123")
	 *   )
	 * )
	 * @SWG\Response(
	 *   response=404, description="Not found.",
	 *   @SWG\Schema(
	 *    @SWG\Property(property="id", type="string", example="invalid user"),
	 *   )
	 * )
	 * 
	 */
	public function complete(Request $request, OrderService $orderService, int $customerId): JsonResponse
	{
		try {
			$resp = $orderService->complete($customerId)->toArray();
			return $this->json($resp, Response::HTTP_OK);
		} catch (UidValidatorException $e) {
			return $this->json($e->getErrors(), Response::HTTP_NOT_FOUND);
		} catch (\Exception $e) {
			if (method_exists($e, 'getErrors')) {
				return $this->json($e->getErrors(), Response::HTTP_BAD_REQUEST);
			} else {
				return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
			}
		}
	}

	/**
	 * View user's order.
	 * 
	 * @Route("/users/{id_user}/orders/{id}", methods={"GET"})
	 * @SWG\Tag(name="6. order")
	 * @SWG\Response(
	 *   response=404, description="Not found.",
	 *   @SWG\Schema(
	 *    @SWG\Property(property="id", type="string", example="Invalid order."),
	 *   )
	 * )
	 * 
	 * @param OrderRepository $repo
	 * @param int $id_user
	 * @param int $id
	 * @return JsonResponse
	 */
	public function getUsersOrderById(OrderRepository $repo, int $id_user, int $id): JsonResponse
	{
		try {
			$resp = $repo->mustFindUsersOrder($id_user, $id)->toArray([], [Order::PRODUCTS]);
			return $this->json($resp, Response::HTTP_OK);
		} catch (OrderValidatorException $e) {
			return $this->json($e->getErrors(), Response::HTTP_NOT_FOUND);
		} catch (\Exception $e) {
			if (method_exists($e, 'getErrors')) {
				return $this->json($e->getErrors(), Response::HTTP_BAD_REQUEST);
			} else {
				return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
			}
		}
	}

	/**
	 * View user's order.
	 * #40 TODO: Replace this with Order schema.
	 * 
	 * @Route("/users/{id_user}/orders", methods={"GET"})
	 * @SWG\Tag(name="6. order")
	 * @SWG\Response(
	 *   response=404, description="Not found.",
	 *   @SWG\Schema(
	 *    @SWG\Property(property="id", type="string", example="Invalid order."),
	 *   )
	 * )
	 * 
	 * @param OrderRepository $repo
	 * @param int $id_user
	 * @return JsonResponse
	 */
	public function getUsersOrders(OrderRepository $repo, int $id_user): JsonResponse
	{
		try {
			$resp = [];
			$orders = $repo->mustFindUsersOrders($id_user);
			foreach ($orders as $order) {
				$resp[] = $order->toArray([], [Order::PRODUCTS]);
			}
			return $this->json($resp, Response::HTTP_OK);
		} catch (OrderValidatorException $e) {
			return $this->json($e->getErrors(), Response::HTTP_NOT_FOUND);
		} catch (\Exception $e) {
			return $this->json($e->getErrors(), Response::HTTP_BAD_REQUEST);
		}
	}
}
