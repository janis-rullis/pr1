<?php
namespace App\Entity\v2;

use Doctrine\ORM\Mapping as ORM;
use \App\Helper\EnumType;
use \App\Exception\OrderShippingValidatorException;
use \App\Entity\v2\OrderProduct;

/**
 * @ORM\Entity(repositoryClass="App\Repository\v2\OrderRepository")
 * @ORM\Table(name="v2_order")
 */
class Order
{

	// #40 Fields.
	const ID = "id";
	const IS_EXPRESS = "is_express";
	const IS_DOMESTIC = "is_domestic";
	const ORDER_ID = "order_id";
	const OWNER_NAME = "name";
	const OWNER_SURNAME = "surname";
	const STREET = "street";
	const STATE = "state";
	const ZIP = "zip";
	const COUNTRY = "country";
	const PHONE = "phone";
	const PRODUCT_COST = "product_cost";
	const SHIPPING_COST = "shipping_cost";
	const TOTAL_COST = "total_cost";
	const STATUS = 'status';
	const CUSTOMER_ID = 'customer_id';
	// #40 Non-db keys.
	const PRODUCTS = "products";
	const SHIPPING = "shipping";
	// #40 Values.
	const COMPLETED = 'completed';
	const DRAFT = 'draft';
	// #40 Messages.
	const REQUIRE_IS_DOMESTIC = 'Set `is_domestic` before `is_express`.';
	const EXPRESS_ONLY_IN_DOMESTIC_REGION = "Express shipping is allowed only in domestic regions.";
	const CANT_CREATE = "Cannot create a draft order. Please, contact our support.";
	const FIELD_IS_MISSING = ' field is missing.';
	const MUST_HAVE_PRODUCTS = "Must have at least 1 product.";
	const MUST_HAVE_SHIPPING_SET = "The shipping must be set before completing the order.";
	const INVALID = 'Invalid order.';
	// #40 Key collections - used for data parsing.
	// #40 Default fields to display to public. Used in repo's `getField()`.
	const PUB_FIELDS = [
		self::ID, self::IS_DOMESTIC, self::IS_EXPRESS,
		self::SHIPPING_COST, self::PRODUCT_COST, self::TOTAL_COST, self::OWNER_NAME,
		self::OWNER_SURNAME, self::STREET, self::COUNTRY, self::PHONE, self::STATE, self::ZIP
	];

	// #40 TODO: Convert to const.
	public static $requireds = [self::OWNER_NAME, self::OWNER_SURNAME, self::STREET, self::COUNTRY, self::PHONE, self::IS_EXPRESS];

	/**
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 * @ORM\Column(type="integer")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", length=20, nullable=true)
	 */
	private $status;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $customer_id;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $product_cost;

	/**
	 * @ORM\Column(type="string", length=1, nullable=true)
	 */
	private $is_domestic;

	/**
	 * @ORM\Column(type="string", length=1, nullable=true)
	 */
	private $is_express;

	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $shipping_cost;

	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $total_cost;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $created_at;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $updated_at;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $deleted_at;

	/**
	 * @ORM\Column(type="string", length=20, nullable=true)
	 */
	private $sys_info;

	/**
	 * @ORM\Column(type="string", length=30)
	 */
	private $name;

	/**
	 * @ORM\Column(type="string", length=30)
	 */
	private $surname;

	/**
	 * @ORM\Column(type="string", length=50)
	 */
	private $street;

	/**
	 * @ORM\Column(type="string", length=40)
	 */
	private $country;

	/**
	 * @ORM\Column(type="string", length=30)
	 */
	private $phone;

	/**
	 * @ORM\Column(type="string", length=30, nullable=true)
	 */
	private $state;

	/**
	 * @ORM\Column(type="string", length=20, nullable=true)
	 */
	private $zip;

	/**
	 * #40 Store order's products when called `getProducts()`.
	 * #40 Annotation based ManyToMany relation that collects order's products.
	 * https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/cookbook/aggregate-fields.html
	 * https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/annotations-reference.html#annref_joincolumns
	 * https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
	 * https://www.doctrine-project.org/api/collections/latest/Doctrine/Common/Collections/ArrayCollection.html
	 * @ORM\ManyToMany(targetEntity="OrderProduct")
	 * @ORM\JoinTable(name="v2_order_product",
	 *      joinColumns={@ORM\JoinColumn(name="order_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="id", referencedColumnName="id", unique=true)}
	 * )
	 */
	private $products;

	/**
	 * #40 Collect order's products.
	 * Collected using annotation JOIN. See `$products`.
	 * @return type
	 */
	public function getProducts()
	{
		return $this->products;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getStatus(): ?string
	{
		return $this->status;
	}

	public function setStatus(?string $status): self
	{
		$this->status = $status;

		return $this;
	}

	public function getCustomerId(): ?int
	{
		return $this->customer_id;
	}

	public function setCustomerId(int $customer_id): self
	{
		$this->customer_id = $customer_id;

		return $this;
	}

	public function getProductCost(): ?int
	{
		return $this->product_cost;
	}

	public function setProductCost(int $product_cost): self
	{
		$this->product_cost = $product_cost;

		return $this;
	}

	public function getIsDomestic(): ?string
	{
		return $this->is_domestic;
	}

	public function setIsDomestic($is_domestic): self
	{
		#40 Convert the value to defined enum values.
		$this->is_domestic = EnumType::parse($is_domestic);

		return $this;
	}

	public function getIsExpress(): ?string
	{
		return $this->is_express;
	}

	public function setIsExpress($is_express): self
	{
		// #40 Convert the value to defined enum values.
		$is_express = EnumType::parse($is_express);

		// #40 Require the `is_domestic` to be set first and to match the region.
		if (empty($this->getIsDomestic())) {
			throw new OrderShippingValidatorException([self::IS_EXPRESS => self::REQUIRE_IS_DOMESTIC], 1);
		}
		if ($this->getIsDomestic() === 'n' && $is_express === 'y') {
			throw new OrderShippingValidatorException([self::IS_EXPRESS => self::EXPRESS_ONLY_IN_DOMESTIC_REGION], 2);
		}

		$this->is_express = $is_express;

		return $this;
	}

	public function getShippingCost(): ?int
	{
		return $this->shipping_cost;
	}

	public function setShippingCost(?int $shipping_cost): self
	{
		$this->shipping_cost = $shipping_cost;

		return $this;
	}

	public function getTotalCost(): ?int
	{
		return $this->total_cost;
	}

	public function setTotalCost(?int $total_cost): self
	{
		$this->total_cost = $total_cost;

		return $this;
	}

	public function getCreatedAt(): ?\DateTimeInterface
	{
		return $this->created_at;
	}

	public function setCreatedAt(?\DateTimeInterface $created_at): self
	{
		$this->created_at = $created_at;

		return $this;
	}

	public function getUpdatedAt(): ?\DateTimeInterface
	{
		return $this->updated_at;
	}

	public function setUpdatedAt(?\DateTimeInterface $updated_at): self
	{
		$this->updated_at = $updated_at;

		return $this;
	}

	public function getDeletedAt(): ?\DateTimeInterface
	{
		return $this->deleted_at;
	}

	public function setDeletedAt(?\DateTimeInterface $deleted_at): self
	{
		$this->deleted_at = $deleted_at;

		return $this;
	}

	public function getSysInfo(): ?string
	{
		return $this->sys_info;
	}

	public function setSysInfo(?string $sys_info): self
	{
		$this->sys_info = $sys_info;

		return $this;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getSurname(): ?string
	{
		return $this->surname;
	}

	public function setSurname(string $surname): self
	{
		$this->surname = $surname;

		return $this;
	}

	public function getStreet(): ?string
	{
		return $this->street;
	}

	public function setStreet(string $street): self
	{
		$this->street = $street;

		return $this;
	}

	public function getCountry(): ?string
	{
		return $this->country;
	}

	public function setCountry(string $country): self
	{
		$this->country = $country;

		return $this;
	}

	public function getPhone(): ?string
	{
		return $this->phone;
	}

	public function setPhone(string $phone): self
	{
		$this->phone = $phone;

		return $this;
	}

	public function getState(): ?string
	{
		return $this->state;
	}

	public function setState(?string $state): self
	{
		$this->state = $state;

		return $this;
	}

	public function getZip(): ?string
	{
		return $this->zip;
	}

	public function setZip(?string $zip): self
	{
		$this->zip = $zip;

		return $this;
	}

	/**
	 * #40 Convert the Entity to array in unified manner. 
	 * Will give same result in different endpoints.
	 * 
	 * @param array $fields
	 * @return array
	 */
	public function toArray(?array $fields = [], $relations = []): array
	{
		$return = [];
		// #40 Contains most popular fields. Add a field is necessary.
		$allFields = [
			self::ID => $this->getId(), self::STATUS => $this->getStatus(),
			self::IS_DOMESTIC => $this->getIsDomestic(), self::IS_EXPRESS => $this->getIsExpress(),
			self::SHIPPING_COST => $this->getShippingCost(), self::PRODUCT_COST => $this->getProductCost(),
			self::TOTAL_COST => $this->getTotalCost(), self::OWNER_NAME => $this->getName(),
			self::OWNER_SURNAME => $this->getSurname(), self::STREET => $this->getStreet(), self::COUNTRY => $this->getCountry(),
			self::PHONE => $this->getPhone(), self::STATE => $this->getState(), self::ZIP => $this->getZip()
		];

		// #40 Fill order's fields.
		if (empty($fields)) {
			$return = $allFields;
		} else {
			foreach ($fields as $field) {
				$return[$field] = isset($allFields[$field]) ? $allFields[$field] : null;
			}
		}
		// #40 Fill relations.
		if (!empty($relations)) {
			foreach ($relations as $relation) {
				switch ($relation) {
					case Order::PRODUCTS:
						$products = $this->getProducts();
						foreach ($products as $product) {
							$return[Order::PRODUCTS][] = $product->toArray();
						}
						break;
					default: null;
				}
			}
		}

		return $return;
	}
}
