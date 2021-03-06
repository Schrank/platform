<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Test\DataAbstractionLayer\Field;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;

class BlacklistRuleFieldTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EntityRepository
     */
    private $repository;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('FEATURE_NEXT_10514', $this);
        $this->connection = $this->getContainer()->get(Connection::class);
        $this->repository = $this->getContainer()->get('product.repository');
    }

    public function testReadEntityWithBlacklist(): void
    {
        $product1 = Uuid::randomHex();
        $product2 = Uuid::randomHex();
        $product3 = Uuid::randomHex();

        $rule1 = Uuid::randomHex();
        $rule2 = Uuid::randomHex();
        $rule3 = Uuid::randomHex();

        $products = [
            [
                'id' => $product1,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test'],
                'blacklistIds' => [$rule1, $rule2],
            ],
            [
                'id' => $product2,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test'],
                'blacklistIds' => [$rule3],
            ],
            [
                'id' => $product3,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test'],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $criteria = new Criteria([$product1, $product2, $product3]);

        //context without rules should return the product
        $context = $this->createContextWithRules();
        $products = $this->repository->search($criteria, $context);
        static::assertTrue($products->has($product1));
        static::assertTrue($products->has($product2));
        static::assertTrue($products->has($product3));

        //context with rule which isn't added to the product should return the product
        $context = $this->createContextWithRules([$rule3]);
        $products = $this->repository->search($criteria, $context);
        static::assertTrue($products->has($product1));
        static::assertFalse($products->has($product2));
        static::assertTrue($products->has($product3));

        //context with rule which is added to the product should return the product
        $context = $this->createContextWithRules([$rule1]);
        $products = $this->repository->search($criteria, $context);
        static::assertFalse($products->has($product1));
        static::assertTrue($products->has($product2));
        static::assertTrue($products->has($product3));

        $context = $this->createContextWithRules([$rule2]);
        $products = $this->repository->search($criteria, $context);
        static::assertFalse($products->has($product1));
        static::assertTrue($products->has($product2));
        static::assertTrue($products->has($product3));

        $context = $this->createContextWithRules([$rule1, $rule2]);
        $products = $this->repository->search($criteria, $context);
        static::assertFalse($products->has($product1));
        static::assertTrue($products->has($product2));
        static::assertTrue($products->has($product3));

        $context = $this->createContextWithRules([$rule1, $rule3]);
        $products = $this->repository->search($criteria, $context);
        static::assertFalse($products->has($product1));
        static::assertFalse($products->has($product2));
        static::assertTrue($products->has($product3));
    }

    public function testInheritedBlacklist(): void
    {
        $parentId1 = Uuid::randomHex();
        $productId1 = Uuid::randomHex();

        $parentId2 = Uuid::randomHex();
        $productId2 = Uuid::randomHex();

        $parentId3 = Uuid::randomHex();
        $productId3 = Uuid::randomHex();

        $rule1 = Uuid::randomHex();
        $rule2 = Uuid::randomHex();
        $rule3 = Uuid::randomHex();

        $products = [
            [
                'id' => $parentId1,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test'],
                'blacklistIds' => [$rule1, $rule2],
            ],
            [
                'id' => $productId1,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'parentId' => $parentId1,
            ],

            [
                'id' => $parentId2,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test'],
            ],
            [
                'id' => $productId2,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'parentId' => $parentId2,
                'blacklistIds' => [$rule1, $rule2],
            ],

            [
                'id' => $parentId3,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test'],
            ],
            [
                'id' => $productId3,
                'productNumber' => Uuid::randomHex(),
                'parentId' => $parentId3,
                'stock' => 1,
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $criteria = new Criteria([$productId1, $productId2, $productId3]);

        //context without rules should return the product
        $context = $this->createContextWithRules();
        $products = $this->repository->search($criteria, $context);
        static::assertTrue($products->has($productId1));
        static::assertTrue($products->has($productId2));
        static::assertTrue($products->has($productId3));

        //context with rule which isn't added to the product should return the product
        $context = $this->createContextWithRules([$rule3]);
        $products = $this->repository->search($criteria, $context);
        static::assertTrue($products->has($productId1));
        static::assertTrue($products->has($productId2));
        static::assertTrue($products->has($productId3));

        //context with rule which is added to the product should return the product
        $context = $this->createContextWithRules([$rule1]);
        $products = $this->repository->search($criteria, $context);
        static::assertFalse($products->has($productId1));
        static::assertFalse($products->has($productId2));
        static::assertTrue($products->has($productId3));

        $context = $this->createContextWithRules([$rule2]);
        $products = $this->repository->search($criteria, $context);
        static::assertFalse($products->has($productId1));
        static::assertFalse($products->has($productId2));
        static::assertTrue($products->has($productId3));

        $context = $this->createContextWithRules([$rule1, $rule2]);
        $products = $this->repository->search($criteria, $context);
        static::assertFalse($products->has($productId1));
        static::assertFalse($products->has($productId2));
        static::assertTrue($products->has($productId3));

        $context = $this->createContextWithRules([$rule1, $rule3]);
        $products = $this->repository->search($criteria, $context);
        static::assertFalse($products->has($productId1));
        static::assertFalse($products->has($productId2));
        static::assertTrue($products->has($productId3));
    }

    public function testSearchBlacklistedRule(): void
    {
        $product1 = Uuid::randomHex();
        $product2 = Uuid::randomHex();
        $product3 = Uuid::randomHex();

        $parent1 = Uuid::randomHex();
        $parent2 = Uuid::randomHex();
        $parent3 = Uuid::randomHex();

        $product4 = Uuid::randomHex();
        $product5 = Uuid::randomHex();
        $product6 = Uuid::randomHex();

        $manufacturerId = Uuid::randomHex();

        $rule1 = Uuid::randomHex();
        $rule2 = Uuid::randomHex();
        $rule3 = Uuid::randomHex();

        $products = [
            [
                'id' => $parent1,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'blacklistIds' => [$rule1, $rule2],
            ],
            //child has inherited blacklist
            [
                'id' => $product1,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'parentId' => $parent1,
            ],

            [
                'id' => $parent2,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
            ],
            //child has blacklist, parent not
            [
                'id' => $product2,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'parentId' => $parent2,
                'blacklistIds' => [$rule1, $rule2],
            ],

            [
                'id' => $parent3,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
            ],
            //child and parent have no blacklist
            [
                'id' => $product3,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'parentId' => $parent3,
            ],
            [
                'id' => $product4,
                'productNumber' => Uuid::randomHex(),
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'stock' => 1,
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'blacklistIds' => [$rule1, $rule2],
            ],
            [
                'id' => $product5,
                'productNumber' => Uuid::randomHex(),
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'stock' => 1,
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'blacklistIds' => [$rule3],
            ],
            [
                'id' => $product6,
                'productNumber' => Uuid::randomHex(),
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'stock' => 1,
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product.manufacturerId', $manufacturerId));

        //context without rules should return the product
        $context = $this->createContextWithRules();
        $result = $this->repository->searchIds($criteria, $context);
        static::assertContains($product1, $result->getIds());
        static::assertContains($product2, $result->getIds());
        static::assertContains($product3, $result->getIds());
        static::assertContains($product4, $result->getIds());
        static::assertContains($product5, $result->getIds());
        static::assertContains($product6, $result->getIds());

        //context without rules should return the product
        $context = $this->createContextWithRules([$rule3]);
        $result = $this->repository->searchIds($criteria, $context);
        static::assertContains($product1, $result->getIds());
        static::assertContains($product2, $result->getIds());
        static::assertContains($product3, $result->getIds());
        static::assertContains($product4, $result->getIds());
        static::assertNotContains($product5, $result->getIds());
        static::assertContains($product6, $result->getIds());

        //context without rules should return the product
        $context = $this->createContextWithRules([$rule1]);
        $result = $this->repository->searchIds($criteria, $context);
        static::assertNotContains($product1, $result->getIds());
        static::assertNotContains($product2, $result->getIds());
        static::assertContains($product3, $result->getIds());
        static::assertNotContains($product4, $result->getIds());
        static::assertContains($product5, $result->getIds());
        static::assertContains($product6, $result->getIds());

        //context without rules should return the product
        $context = $this->createContextWithRules([$rule1, $rule3]);
        $result = $this->repository->searchIds($criteria, $context);
        static::assertNotContains($product1, $result->getIds());
        static::assertNotContains($product2, $result->getIds());
        static::assertContains($product3, $result->getIds());
        static::assertNotContains($product4, $result->getIds());
        static::assertNotContains($product5, $result->getIds());
        static::assertContains($product6, $result->getIds());
    }

    public function testSearchWithOneToManyBlacklist(): void
    {
        $product1 = Uuid::randomHex();
        $product2 = Uuid::randomHex();
        $product3 = Uuid::randomHex();
        $product4 = Uuid::randomHex();

        $manufacturerId = Uuid::randomHex();
        $manufacturerId2 = Uuid::randomHex();

        $rule1 = Uuid::randomHex();
        $rule2 = Uuid::randomHex();
        $rule3 = Uuid::randomHex();

        $products = [
            [
                'id' => $product1,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test',
                'ean' => __FUNCTION__,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'blacklistIds' => [$rule1],
            ],
            [
                'id' => $product2,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test',
                'ean' => __FUNCTION__,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'blacklistIds' => [$rule2],
            ],
            [
                'id' => $product3,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test',
                'ean' => __FUNCTION__,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'blacklistIds' => [$rule3],
            ],

            [
                'id' => $product4,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test',
                'ean' => __FUNCTION__,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId2],
                'blacklistIds' => [$rule3],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product_manufacturer.products.ean', __FUNCTION__));

        $manufacturerRepository = $this->getContainer()->get('product_manufacturer.repository');

        $context = $this->createContextWithRules();
        $result = $manufacturerRepository->searchIds($criteria, $context);
        static::assertContains($manufacturerId, $result->getIds());
        static::assertContains($manufacturerId2, $result->getIds());

        $context = $this->createContextWithRules([$rule1]);
        $result = $manufacturerRepository->searchIds($criteria, $context);
        static::assertContains($manufacturerId, $result->getIds());
        static::assertContains($manufacturerId2, $result->getIds());

        $context = $this->createContextWithRules([$rule3]);
        $result = $manufacturerRepository->searchIds($criteria, $context);
        static::assertContains($manufacturerId, $result->getIds());
        static::assertNotContains($manufacturerId2, $result->getIds());

        $context = $this->createContextWithRules([$rule1, $rule2, $rule3]);
        $result = $manufacturerRepository->searchIds($criteria, $context);
        static::assertNotContains($manufacturerId, $result->getIds());
        static::assertNotContains($manufacturerId2, $result->getIds());
    }

    public function testSearchWithManyToManyBlacklist(): void
    {
        $product1 = Uuid::randomHex();
        $product2 = Uuid::randomHex();
        $product3 = Uuid::randomHex();
        $product4 = Uuid::randomHex();

        $manufacturerId = Uuid::randomHex();
        $categoryId = Uuid::randomHex();
        $categoryId2 = Uuid::randomHex();

        $rule1 = Uuid::randomHex();
        $rule2 = Uuid::randomHex();
        $rule3 = Uuid::randomHex();

        $products = [
            [
                'id' => $product1,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test',
                'ean' => __FUNCTION__,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'categories' => [
                    ['name' => 'test', 'id' => $categoryId],
                ],
                'blacklistIds' => [$rule1],
            ],
            [
                'id' => $product2,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test',
                'ean' => __FUNCTION__,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'categories' => [
                    ['name' => 'test', 'id' => $categoryId],
                ],
                'blacklistIds' => [$rule2],
            ],
            [
                'id' => $product3,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test',
                'ean' => __FUNCTION__,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'categories' => [
                    ['name' => 'test', 'id' => $categoryId],
                ],
                'blacklistIds' => [$rule3],
            ],

            [
                'id' => $product4,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test',
                'ean' => __FUNCTION__,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'categories' => [
                    ['name' => 'test', 'id' => $categoryId2],
                ],
                'blacklistIds' => [$rule3],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('category.products.ean', __FUNCTION__));

        $categoryRepository = $this->getContainer()->get('category.repository');

        $context = $this->createContextWithRules();
        $result = $categoryRepository->searchIds($criteria, $context);
        static::assertContains($categoryId, $result->getIds());
        static::assertContains($categoryId2, $result->getIds());

        $context = $this->createContextWithRules([$rule1]);
        $result = $categoryRepository->searchIds($criteria, $context);
        static::assertContains($categoryId, $result->getIds());
        static::assertContains($categoryId2, $result->getIds());

        $context = $this->createContextWithRules([$rule3]);
        $result = $categoryRepository->searchIds($criteria, $context);
        static::assertContains($categoryId, $result->getIds());
        static::assertNotContains($categoryId2, $result->getIds());

        $context = $this->createContextWithRules([$rule1, $rule2, $rule3]);
        $result = $categoryRepository->searchIds($criteria, $context);
        static::assertNotContains($categoryId, $result->getIds());
        static::assertNotContains($categoryId2, $result->getIds());
    }

    public function testAggregationBlacklist(): void
    {
        $product1 = Uuid::randomHex();
        $product2 = Uuid::randomHex();
        $product3 = Uuid::randomHex();

        $manufacturerId = Uuid::randomHex();

        $rule1 = Uuid::randomHex();
        $rule2 = Uuid::randomHex();
        $rule3 = Uuid::randomHex();

        $products = [
            [
                'id' => $product1,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'ean' => $product1,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'blacklistIds' => [$rule1, $rule2],
            ],
            [
                'id' => $product2,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'ean' => $product2,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'blacklistIds' => [$rule3],
            ],
            [
                'id' => $product3,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'ean' => $product3,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product.manufacturerId', $manufacturerId));
        $criteria->addAggregation(new TermsAggregation('eans', 'product.ean'));

        $context = $this->createContextWithRules();
        $result = $this->repository->aggregate($criteria, $context)->get('eans');

        static::assertInstanceOf(TermsResult::class, $result);
        $values = $result->getKeys();
        static::assertContains($product1, $values);
        static::assertContains($product2, $values);
        static::assertContains($product3, $values);

        $context = $this->createContextWithRules([$rule1]);
        $result = $this->repository->aggregate($criteria, $context)->get('eans');
        static::assertInstanceOf(TermsResult::class, $result);
        $values = $result->getKeys();
        static::assertNotContains($product1, $values);
        static::assertContains($product2, $values);
        static::assertContains($product3, $values);

        $context = $this->createContextWithRules([$rule2, $rule3]);
        $result = $this->repository->aggregate($criteria, $context)->get('eans');
        static::assertInstanceOf(TermsResult::class, $result);
        $values = $result->getKeys();
        static::assertNotContains($product1, $values);
        static::assertNotContains($product2, $values);
        static::assertContains($product3, $values);
    }

    public function testAggregationWithOneToManyBlacklist(): void
    {
        $product1 = Uuid::randomHex();
        $product2 = Uuid::randomHex();
        $product3 = Uuid::randomHex();

        $manufacturerId = Uuid::randomHex();

        $rule1 = Uuid::randomHex();
        $rule2 = Uuid::randomHex();
        $rule3 = Uuid::randomHex();

        $products = [
            [
                'id' => $product1,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'blacklistIds' => [$rule1, $rule2],
            ],
            [
                'id' => $product2,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'blacklistIds' => [$rule3],
            ],
            [
                'id' => $product3,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product_manufacturer.id', $manufacturerId));
        $criteria->addAggregation(new TermsAggregation('products', 'product_manufacturer.products.id'));

        /** @var EntityRepositoryInterface $manufacturerRepository */
        $manufacturerRepository = $this->getContainer()->get('product_manufacturer.repository');
        $context = $this->createContextWithRules();

        $result = $manufacturerRepository->aggregate($criteria, $context)->get('products');
        static::assertInstanceOf(TermsResult::class, $result);
        $values = $result->getKeys();
        static::assertContains($product1, $values);
        static::assertContains($product2, $values);
        static::assertContains($product3, $values);

        $context = $this->createContextWithRules([$rule1]);
        $result = $manufacturerRepository->aggregate($criteria, $context)->get('products');

        static::assertInstanceOf(TermsResult::class, $result);
        $values = $result->getKeys();
        static::assertNotContains($product1, $values);
        static::assertContains($product2, $values);
        static::assertContains($product3, $values);

        $context = $this->createContextWithRules([$rule2, $rule3]);
        $result = $manufacturerRepository->aggregate($criteria, $context)->get('products');
        static::assertInstanceOf(TermsResult::class, $result);
        $values = $result->getKeys();
        static::assertNotContains($product1, $values);
        static::assertNotContains($product2, $values);
        static::assertContains($product3, $values);
    }

    public function testAggregationWithManyToManyBlacklist(): void
    {
        $product1 = Uuid::randomHex();
        $product2 = Uuid::randomHex();
        $product3 = Uuid::randomHex();

        $manufacturerId = Uuid::randomHex();
        $categoryId = Uuid::randomHex();

        $rule1 = Uuid::randomHex();
        $rule2 = Uuid::randomHex();
        $rule3 = Uuid::randomHex();

        $products = [
            [
                'id' => $product1,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'categories' => [
                    ['id' => $categoryId, 'name' => 'test'],
                ],
                'blacklistIds' => [$rule1, $rule2],
            ],
            [
                'id' => $product2,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'categories' => [
                    ['id' => $categoryId, 'name' => 'test'],
                ],
                'blacklistIds' => [$rule3],
            ],
            [
                'id' => $product3,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'tax' => ['name' => 'test', 'taxRate' => 15],
                'name' => 'test product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
                'categories' => [
                    ['id' => $categoryId, 'name' => 'test'],
                ],
            ],
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('category.id', $categoryId));
        $criteria->addAggregation(new TermsAggregation('products', 'category.products.id'));

        /** @var EntityRepositoryInterface $categoryRepository */
        $categoryRepository = $this->getContainer()->get('category.repository');
        $context = $this->createContextWithRules();
        $result = $categoryRepository->aggregate($criteria, $context)->get('products');

        static::assertInstanceOf(TermsResult::class, $result);
        $values = $result->getKeys();
        static::assertContains($product1, $values);
        static::assertContains($product2, $values);
        static::assertContains($product3, $values);

        $context = $this->createContextWithRules([$rule1]);
        $result = $categoryRepository->aggregate($criteria, $context)->get('products');
        static::assertInstanceOf(TermsResult::class, $result);
        $values = $result->getKeys();
        static::assertNotContains($product1, $values);
        static::assertContains($product2, $values);
        static::assertContains($product3, $values);

        $context = $this->createContextWithRules([$rule2, $rule3]);
        $result = $categoryRepository->aggregate($criteria, $context)->get('products');
        static::assertInstanceOf(TermsResult::class, $result);
        $values = $result->getKeys();
        static::assertNotContains($product1, $values);
        static::assertNotContains($product2, $values);
        static::assertContains($product3, $values);
    }

    public function testPaginatedAssociationWithBlacklist(): void
    {
        $manufacturerId = Uuid::randomHex();
        $ruleId = Uuid::randomHex();
        $ruleId2 = Uuid::randomHex();

        $default = [
            'productNumber' => Uuid::randomHex(),
            'stock' => 1,
            'tax' => ['name' => 'test', 'taxRate' => 15, 'id' => $manufacturerId],
            'name' => 'test product',
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
            'manufacturer' => ['name' => 'test', 'id' => $manufacturerId],
        ];

        $default2 = array_merge($default, ['productNumber' => Uuid::randomHex()]);

        $withRules = array_merge($default, ['blacklistIds' => [$ruleId], 'productNumber' => Uuid::randomHex()]);

        $withRules2 = array_merge($default, ['blacklistIds' => [$ruleId], 'productNumber' => Uuid::randomHex()]);

        $products = [
            $default,
            $withRules,
            $withRules2,
            $default2,
        ];

        $this->repository->create($products, Context::createDefaultContext());

        $criteria = new Criteria([$manufacturerId]);
        $criteria->getAssociation('products')->setLimit(4);

        $repo = $this->getContainer()->get('product_manufacturer.repository');

        $context = $this->createContextWithRules();
        /** @var ProductManufacturerEntity $manufacturer */
        $manufacturer = $repo->search($criteria, $context)->get($manufacturerId);

        //test if all products can be read if context contains no rules
        static::assertInstanceOf(ProductManufacturerEntity::class, $manufacturer);

        static::assertInstanceOf(ProductCollection::class, $manufacturer->getProducts());
        static::assertCount(4, $manufacturer->getProducts());

        //test if two of four products can be read if context contains no rule
        $criteria = new Criteria([$manufacturerId]);
        $criteria->getAssociation('products')->setLimit(2);

        $repo = $this->getContainer()->get('product_manufacturer.repository');

        $context = $this->createContextWithRules();
        /** @var ProductManufacturerEntity $manufacturer */
        $manufacturer = $repo->search($criteria, $context)->get($manufacturerId);

        static::assertInstanceOf(ProductManufacturerEntity::class, $manufacturer);
        static::assertInstanceOf(ProductCollection::class, $manufacturer->getProducts());
        static::assertCount(2, $manufacturer->getProducts());

        //test if two of four products can be read if context contains no rule
        $criteria = new Criteria([$manufacturerId]);
        $criteria->getAssociation('products')->setLimit(4);

        $repo = $this->getContainer()->get('product_manufacturer.repository');

        $context = $this->createContextWithRules([$ruleId, $ruleId2]);
        /** @var ProductManufacturerEntity $manufacturer */
        $manufacturer = $repo->search($criteria, $context)->get($manufacturerId);

        static::assertInstanceOf(ProductManufacturerEntity::class, $manufacturer);
        static::assertInstanceOf(ProductCollection::class, $manufacturer->getProducts());
        static::assertCount(2, $manufacturer->getProducts());
    }

    private function createContextWithRules(array $ruleIds = []): Context
    {
        return new Context(new SystemSource(), $ruleIds, Defaults::CURRENCY, [Defaults::LANGUAGE_SYSTEM], Defaults::LIVE_VERSION, 1.0, true);
    }
}
