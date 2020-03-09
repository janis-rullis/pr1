<?php
namespace App\v2;

use \App\Interfaces\IProductRepo;
use \App\Interfaces\IUserRepo;
use \App\Entity\v2\OrderProduct;

class OrderProductCreator
{

	private $userRepo;
	private $productRepo;

	public function __construct(IProductRepo $productRepo, IUserRepo $userRepo)
	{
		$this->userRepo = $userRepo;
		$this->productRepo = $productRepo;
	}

	/**
	 * #38 Validate, prepare and write to db.
	 * 
	 * @param array $data
	 * @return type
	 */
	public function handle(array $data)
	{
		$return = ['errors' => [], 'status' => false, 'data' => null];

		// #38 Validate and prepare the item.
		$item = $this->prepare($data);

		// #38 Write data to db only after it's validated and prepared.
		if (empty($item['errors'])) {
			$this->entityManager->persist($item['data']);
			$this->entityManager->flush();

			// #38 TODO: Check and set into `$return` DB errors here.
			$return = $item;
		} else {
			$return = $item;
		}
		return $return;
	}

	/**
	 * #38 Validate and prepare the item.
	 * 
	 * @param array $datas
	 * @return type
	 */
	public function prepare(array $data)
	{
		$return = ['errors' => [], 'status' => false, 'data' => null];
		$validator = new \App\v2\OrderProductValidator;

		// #38 Check if all required fields are passed.
		$status = $validator->hasRequiredKeys($data);
		if ($status !== true) {
			$return['errors'] = $status;
			return $return;
		}

		// TODO: Should this be moved to the Validator?
		// #38 Check if they exist in the database. Collect seller's and product's information.
		$customer = $this->userRepo->find($data['customer_id']);
		if (empty($customer)) {
			$return['errors']['customer_id'] = ["Invalid 'customer_id'."];
		}
		$product = $this->productRepo->find($data['product_id']);
		if (empty($product)) {
			$return['errors']['product_id'] = ["Invalid 'product_id'."];
		}

		// #38 Prepare the data for writing in the database.
		if (empty($return['errors'])) {

			$seller = $this->userRepo->find($product->getOwnerId());
			if (empty($seller)) {
				$return['errors']['seller_id'] = ["Invalid 'seller_id'."];
			}

			// #38 TODO: Should this me moved to Repo - it works kinda with DB?
			$item = new OrderProduct();

			// #38 Collect customer's current 'draft' order where all the cart's items should be stored.
			// Create if it doesn't exist yet.
			$item->setOrderId(1);

			// #38 TODO: Should this be done better with SQL JOIN UPDATE?
			$item->setCustomerId($customer->getId());
			$item->setSellerId($seller->getId());
			$item->setProductId($product->getId());
			$item->setProductCost($product->getCost());
			$item->setProductType($product->getType());
			dd($item);

			// TODO.
			$item->setSellerTitle('US');
			$item->setProductTitle('T-shirt / US / Standard / First');
			$item->setIsDomestic('y');
			
			// TODO: Pass to the Validator->validate() to check types.
		}

		return $return;
	}
}
