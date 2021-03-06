<?php

namespace App\Entity;

use App\Exception\OrderShippingValidatorException;
use App\Helper\EnumType;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OrderRepository")
 * @ORM\Table(name="`order`")
 */
class Order
{
    // #40 Fields.
    const ID = 'id';
    const IS_EXPRESS = 'is_express';
    const IS_DOMESTIC = 'is_domestic';
    const ORDER_ID = 'order_id';
    const OWNER_NAME = 'name';
    const OWNER_SURNAME = 'surname';
    const STREET = 'street';
    const STATE = 'state';
    const ZIP = 'zip';
    const COUNTRY = 'country';
    const PHONE = 'phone';
    const PRODUCT_COST = 'product_cost';
    const SHIPPING_COST = 'shipping_cost';
    const TOTAL_COST = 'total_cost';
    const STATUS = 'status';
    const CUSTOMER_ID = 'customer_id';
    // #40 Non-db keys.
    const PRODUCTS = 'products';
    const SHIPPING = 'shipping';
    // #40 Values.
    const COMPLETED = 'completed';
    const DRAFT = 'draft';
    // #40 Messages.
    const REQUIRE_IS_DOMESTIC = 'Set `is_domestic` before `is_express`.';
    const EXPRESS_ONLY_IN_DOMESTIC_REGION = 'Express shipping is allowed only in domestic regions.';
    const CANT_CREATE = 'Cannot create a draft order. Please, contact our support.';
    const FIELD_IS_MISSING = ' field is missing.';
    const MUST_HAVE_PRODUCTS = 'Must have at least 1 product.';
    const MUST_HAVE_SHIPPING_SET = 'The shipping must be set before completing the order.';
    const INVALID = 'Invalid order.';
    const INTERNATIONAL_ORDER = 'international';
    const DOMESTIC_ORDER = 'domestic';
    const NO_NAME = 'name key not set';
    const NO_SURNAME = 'surname key not set';
    const NO_STREET = 'street key not set';
    const NO_ZIP = 'zip code key not set';
    const NO_STATE = 'state key not set';
    const NO_COUNTRY = 'country key not set';
    const NO_PHONE = 'phone key not set';
    const INVALID_NAME = 'name can only consist of letters';
    const INVALID_SURNAME = 'surname can only consist of letters';
    const INVALID_STREET = 'street can only consist of letters, digits, dash (-) and whitespaces';
    const INVALID_ZIP = 'invalid zip code';
    const INVALID_STATE = 'invalid state';
    const INVALID_COUNTRY = 'invalid country';
    const INVALID_PHONE = 'invalid phone number';
    // #40 Key collections - used for data parsing.
    // #40 Default fields to display to public. Used in repo's `getField()`.
    const PUB_FIELDS = [
        self::ID, self::IS_DOMESTIC, self::IS_EXPRESS,
        self::SHIPPING_COST, self::PRODUCT_COST, self::TOTAL_COST, self::OWNER_NAME,
        self::OWNER_SURNAME, self::STREET, self::COUNTRY, self::PHONE, self::STATE, self::ZIP,
    ];
    const VALID_SHIPPING_EXAMPLE = [
        self::OWNER_NAME => 'John', self::OWNER_SURNAME => 'Doe', self::STREET => 'Palm street 25-7',
        self::STATE => 'California', self::ZIP => '60744', self::COUNTRY => 'US',
        self::PHONE => '+1 123 123 123', self::IS_EXPRESS => true,
    ];
    // #40 Required fields when creating a new item.
    const REQUIRED_FIELDS = [self::OWNER_NAME, self::OWNER_SURNAME, self::STREET, self::COUNTRY, self::PHONE, self::IS_EXPRESS];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @SWG\Property(property="id", type="integer", example=1)
     * @Groups({"PUB"})
     */
    private int $id;

    // #78 Handlle a nullable typed prop https://stackoverflow.com/a/61954740
    // #78 Available prop types https://stitcher.io/blog/typed-properties-in-php-74#types-of-types
    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @SWG\Property(property="status", type="string", example="draft")
     * @Groups({"PUB"})
     */
    private ?string $status = null;

    /**
     * @ORM\Column(type="integer")
     * @SWG\Property(property="status", type="customer_id", example=1)
     * @Groups({"PUB"})
     */
    private int $customer_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @SWG\Property(property="product_cost", type="integer", example=1000)
     * @Groups({"PUB"})
     */
    private ?int $product_cost = null;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     * @SWG\Property(property="is_domestic", type="boolean", example=true)
     * @Groups({"PUB"})
     */
    private ?string $is_domestic = null;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     * @SWG\Property(property="is_express", type="boolean", example=true)
     * @Groups({"CREATE", "PUB"})
     */
    private ?string $is_express = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @SWG\Property(property="shipping_cost", type="integer", example=1000)
     * @Groups({"PUB"})
     */
    private ?int $shipping_cost = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @SWG\Property(property="total_cost", type="integer", example=2000)
     * @Groups({"PUB"})
     */
    private ?int $total_cost = null;

    // #78 Date doesn't have an available prop type https://stitcher.io/blog/typed-properties-in-php-74#types-of-types
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
    private ?string $sys_info = null;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     * @SWG\Property(property="name", type="string", example="John")
     * @Groups({"CREATE", "PUB"})
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     * @SWG\Property(property="surname", type="string", example="Doe")
     * @Groups({"CREATE", "PUB"})
     */
    private ?string $surname = null;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @SWG\Property(property="street", type="string", example="Palm street 25-7")
     * @Groups({"CREATE", "PUB"})
     */
    private ?string $street = null;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     * @SWG\Property(property="country", type="string", example="US")
     * @Groups({"CREATE", "PUB"})
     */
    private ?string $country = null;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     * @SWG\Property(property="phone", type="string", example="+1 123 123 123")
     * @Groups({"CREATE", "PUB"})
     */
    private ?string $phone = null;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     * @SWG\Property(property="state", type="string", example="California")
     * @Groups({"CREATE", "PUB"})
     */
    private ?string $state = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @SWG\Property(property="zip", type="string", example="60744")
     * @Groups({"CREATE", "PUB"})
     */
    private ?string $zip = null;

    /**
     * #40 Store order's products when called `getProducts()`.
     * #40 Annotation based ManyToMany relation that collects order's products.
     * https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/cookbook/aggregate-fields.html
     * https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/annotations-reference.html#annref_joincolumns
     * https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
     * https://www.doctrine-project.org/api/collections/latest/Doctrine/Common/Collections/ArrayCollection.html.
     *
     * #58 Handle the array-type property https://tomasvotruba.com/blog/2020/03/23/doctrine-entity-typed-properties-with-php74/#1-the-property
     */
    /**
     * @ORM\ManyToMany(targetEntity="OrderProduct")
     * @ORM\JoinTable(name="order_product",
     *      joinColumns={@ORM\JoinColumn(name="order_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="id", referencedColumnName="id", unique=true)}
     * )
     * @SWG\Property(property="products", type="array", @SWG\Items(@Model(type=OrderProduct::class)))
     * @Groups({"PUB"})
     */
    private ?Collection $products = null;

    /**
     * #40 Collect order's products.
     * Collected using annotation JOIN. See `$products`.
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
        //40 Convert the value to defined enum values.
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
        if ('n' === $this->getIsDomestic() && 'y' === $is_express) {
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
     */
    public function toArray(?array $fields = [], $relations = []): array
    {
        $return = [];
        // #40 Contains most popular fields. Add a field is necessary.
        $return = $this->toArrayFill($fields);

        // #40 Fill relations.
        if (!empty($relations)) {
            foreach ($relations as $relation) {
                switch ($relation) {
                    case Order::PRODUCTS:
                        $products = $this->getProducts();
                        if (!empty($products)) {
                            foreach ($products as $product) {
                                $return[Order::PRODUCTS][] = $product->toArray();
                            }
                        }
                        break;
                    default: null;
                }
            }
        }

        return $return;
    }

    /**
     * #40 Fill order's fields.
     */
    private function toArrayFill(?array $fields = []): array
    {
        $return = [];
        $allFields = [
            self::ID => $this->getId(), self::STATUS => $this->getStatus(),
            self::IS_DOMESTIC => $this->getIsDomestic(), self::IS_EXPRESS => $this->getIsExpress(),
            self::SHIPPING_COST => $this->getShippingCost(), self::PRODUCT_COST => $this->getProductCost(),
            self::TOTAL_COST => $this->getTotalCost(), self::OWNER_NAME => $this->getName(),
            self::OWNER_SURNAME => $this->getSurname(), self::STREET => $this->getStreet(), self::COUNTRY => $this->getCountry(),
            self::PHONE => $this->getPhone(), self::STATE => $this->getState(), self::ZIP => $this->getZip(),
        ];

        if (empty($fields)) {
            return $allFields;
        }

        foreach ($fields as $field) {
            $return[$field] = isset($allFields[$field]) ? $allFields[$field] : null;
        }

        return $return;
    }
}
