<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Sales\Test\Unit\Model\AdminOrder;

use Magento\Backend\Model\Session\Quote as SessionQuote;
use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Customer\Model\Metadata\Form;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Updater;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CreateTest extends TestCase
{
    public const CUSTOMER_ID = 1;

    /**
     * @var Create
     */
    private Create $adminOrderCreate;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var QuoteFactory|MockObject
     */
    private QuoteFactory $quoteFactory;

    /**
     * @var SessionQuote|MockObject
     */
    private SessionQuote $sessionQuote;

    /**
     * @var FormFactory|MockObject
     */
    private FormFactory $formFactory;

    /**
     * @var CustomerInterfaceFactory|MockObject
     */
    private CustomerInterfaceFactory $customerFactory;

    /**
     * @var Updater|MockObject
     */
    private Updater $itemUpdater;

    /**
     * @var Mapper|MockObject
     */
    private Mapper $customerMapper;

    /**
     * @var GroupRepositoryInterface|MockObject
     */
    private GroupRepositoryInterface $groupRepository;

    /**
     * @var DataObjectHelper|MockObject
     */
    private DataObjectHelper $dataObjectHelper;

    /**
     * @var Order|MockObject
     */
    private Order $orderMock;

    /**
     * @var ObjectManagerInterface|ObjectManagerInterface&MockObject|MockObject
     */
    private ObjectManagerInterface $objectManager;

    /**
     * @var ManagerInterface|ManagerInterface&MockObject|MockObject
     */
    private ManagerInterface $messageManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->formFactory = $this->createPartialMock(FormFactory::class, ['create']);
        $this->quoteFactory = $this->createPartialMock(QuoteFactory::class, ['create']);
        $this->customerFactory = $this->createPartialMock(CustomerInterfaceFactory::class, ['create']);

        $this->itemUpdater = $this->createMock(Updater::class);

        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getForCustomer'])
            ->getMockForAbstractClass();

        $this->sessionQuote = $this->getMockBuilder(SessionQuote::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'getStoreId',
                'getCustomerId',
                'setData',
                'setCurrencyId',
                'setCustomerId',
                'setStoreId',
                'setCustomerGroupId',
                'getUseOldShippingMethod'
            ])
            ->onlyMethods(
                [
                    'getQuote',
                    'getData',
                    'getStore'
                ]
            )
            ->getMock();

        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->onlyMethods(['getId'])
            ->getMockForAbstractClass();
        $this->sessionQuote->method('getStore')
            ->willReturn($storeMock);

        $this->customerMapper = $this->getMockBuilder(Mapper::class)
            ->onlyMethods(['toFlatArray'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupRepository = $this->getMockForAbstractClass(GroupRepositoryInterface::class);
        $this->dataObjectHelper = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getEntityId',
                    'getId',
                    'getOrderCurrencyCode',
                    'getCustomerGroupId',
                    'getItemsCollection',
                    'getShippingAddress',
                    'getBillingAddress',
                    'getCouponCode',
                ]
            )
            ->getMock();

        $this->objectManager = $this->createMock(ObjectManagerInterface::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->adminOrderCreate = $objectManagerHelper->getObject(
            Create::class,
            [
                '_objectManager' => $this->objectManager,
                'messageManager' => $this->messageManager,
                'quoteSession' => $this->sessionQuote,
                'metadataFormFactory' => $this->formFactory,
                'customerFactory' => $this->customerFactory,
                'groupRepository' => $this->groupRepository,
                'quoteItemUpdater' => $this->itemUpdater,
                'customerMapper' => $this->customerMapper,
                'dataObjectHelper' => $this->dataObjectHelper,
                'quoteRepository' => $this->quoteRepository,
                'quoteFactory' => $this->quoteFactory,
            ]
        );
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception|LocalizedException
     */
    public function testInitFromOrderItemNoExceptionThrownOnAddProduct(): void
    {
        $orderItemId = $productId = 1;
        $exceptionMessage = 'Exception message';

        $buyRequest = $this->createMock(\Magento\Framework\DataObject::class);

        $orderItem = $this->createMock(\Magento\Sales\Model\Order\Item::class);
        $orderItem->expects($this->once())->method('getId')->willReturn($orderItemId);
        $orderItem->expects($this->once())->method('getProductId')->willReturn($productId);
        $orderItem->expects($this->once())->method('getBuyRequest')->willReturn($buyRequest);
        $orderItem->expects($this->once())->method('getProductOptions')->willReturn(null);

        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->expects($this->once())->method('setStoreId')->willReturnSelf();
        $product->expects($this->once())->method('load')->willReturnSelf();
        $product->expects($this->once())->method('getId')->willReturn($productId);
        $this->objectManager->expects($this->once())->method('create')->willReturn($product);

        $exception = new LocalizedException(__($exceptionMessage));
        $quote = $this->createMock(Quote::class);
        $quote->expects($this->once())
            ->method('addProduct')
            ->with($product, $buyRequest)
            ->willThrowException($exception);
        $this->sessionQuote->method('getQuote')
            ->willReturn($quote);

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__($exceptionMessage))
            ->willReturnSelf();

        $this->adminOrderCreate->initFromOrderItem($orderItem);
    }

    public function testSetAccountData()
    {
        $taxClassId = 1;
        $attributes = [
            ['email', 'user@example.com'],
            ['group_id', 1]
        ];
        $attributeMocks = [];

        foreach ($attributes as $value) {
            $attribute = $this->getMockForAbstractClass(AttributeMetadataInterface::class);
            $attribute->method('getAttributeCode')
                ->willReturn($value[0]);

            $attributeMocks[] = $attribute;
        }

        $customerGroup = $this->getMockForAbstractClass(GroupInterface::class);
        $customerGroup->method('getTaxClassId')
            ->willReturn($taxClassId);
        $customerForm = $this->createMock(Form::class);
        $customerForm->method('getAttributes')
            ->willReturn([$attributeMocks[1]]);
        $customerForm
            ->method('extractData')
            ->willReturn([]);
        $customerForm
            ->method('restoreData')
            ->willReturn(['group_id' => 1]);

        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getPostValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requestMock->expects($this->atLeastOnce())->method('getPostValue')->willReturn(null);
        $customerForm->method('prepareRequest')
            ->willReturn($requestMock);

        $customer = $this->createMock(CustomerInterface::class);
        $this->customerMapper->expects(self::atLeastOnce())
            ->method('toFlatArray')
            ->willReturn(['group_id' => 1]);

        $quote = $this->createMock(Quote::class);
        $quote->method('getCustomer')->willReturn($customer);
        $quote->method('addData')->with(
            [
                'customer_group_id' => $attributes[1][1],
                'customer_tax_class_id' => $taxClassId
            ]
        );
        $quote->method('getStoreId')->willReturn(1);
        $this->dataObjectHelper->method('populateWithArray')
            ->with(
                $customer,
                ['group_id' => 1],
                CustomerInterface::class
            );

        $this->formFactory->method('create')
            ->willReturn($customerForm);
        $this->sessionQuote
            ->method('getQuote')
            ->willReturn($quote);
        $this->customerFactory->method('create')
            ->willReturn($customer);

        $this->groupRepository->method('getById')
            ->willReturn($customerGroup);

        $customer->expects($this->once())
            ->method('setStoreId')
            ->with(1);

        $this->adminOrderCreate->setAccountData(['group_id' => 1]);
    }

    public function testUpdateQuoteItemsNotArray()
    {
        $object = $this->adminOrderCreate->updateQuoteItems('string');
        self::assertEquals($this->adminOrderCreate, $object);
    }

    public function testUpdateQuoteItemsEmptyConfiguredOption()
    {
        $items = [
            1 => [
                'qty' => 10,
                'configured' => false,
                'action' => false
            ]
        ];

        $item = $this->createMock(Item::class);

        $quote = $this->createMock(Quote::class);
        $quote->method('getItemById')
            ->willReturn($item);

        $this->sessionQuote->method('getQuote')
            ->willReturn($quote);
        $this->itemUpdater->method('update')
            ->with(self::equalTo($item), self::equalTo($items[1]))
            ->willReturnSelf();

        $this->adminOrderCreate->setRecollect(false);
        $object = $this->adminOrderCreate->updateQuoteItems($items);
        self::assertEquals($this->adminOrderCreate, $object);
    }

    public function testUpdateQuoteItemsWithConfiguredOption()
    {
        $qty = 100000000;
        $items = [
            1 => [
                'qty' => 10,
                'configured' => true,
                'action' => false
            ]
        ];

        $item = $this->createMock(Item::class);
        $item->method('getQty')
            ->willReturn($qty);

        $quote = $this->createMock(Quote::class);
        $quote->method('updateItem')
            ->willReturn($item);

        $this->sessionQuote
            ->method('getQuote')
            ->willReturn($quote);

        $expectedInfo = $items[1];
        $expectedInfo['qty'] = $qty;
        $this->itemUpdater->method('update')
            ->with(self::equalTo($item), self::equalTo($expectedInfo));

        $this->adminOrderCreate->setRecollect(false);
        $object = $this->adminOrderCreate->updateQuoteItems($items);
        self::assertEquals($this->adminOrderCreate, $object);
    }

    public function testApplyCoupon()
    {
        $couponCode = '123';
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['setCouponCode'])
            ->onlyMethods(['getShippingAddress'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionQuote->method('getQuote')
            ->willReturn($quote);

        $address = $this->getMockBuilder(Address::class)
            ->addMethods(['setCollectShippingRates', 'setFreeShipping'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->method('getShippingAddress')
            ->willReturn($address);
        $quote->method('setCouponCode')
            ->with($couponCode)
            ->willReturnSelf();

        $address->method('setCollectShippingRates')
            ->with(true)
            ->willReturnSelf();
        $address->method('setFreeShipping')
            ->with(0)
            ->willReturnSelf();

        $object = $this->adminOrderCreate->applyCoupon($couponCode);
        self::assertEquals($this->adminOrderCreate, $object);
    }

    public function testGetCustomerCart()
    {
        $storeId = 2;
        $customerId = 2;
        $cartResult = [
            'cart' => true,
        ];

        $this->quoteFactory->expects($this->once())
            ->method('create');
        $this->sessionQuote->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $this->sessionQuote->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $this->quoteRepository->expects($this->once())
            ->method('getForCustomer')
            ->with($customerId, [$storeId])
            ->willReturn($cartResult);

        $this->assertEquals($cartResult, $this->adminOrderCreate->getCustomerCart());
    }

    public function testInitFromOrder()
    {
        $this->sessionQuote->method('getData')
            ->with('reordered')
            ->willReturn(true);

        $address = $this->createPartialMock(
            Address::class,
            [
                'setSameAsBilling',
                'setCustomerAddressId',
                'getSameAsBilling',
            ]
        );
        $address->method('getSameAsBilling')
            ->willReturn(true);
        $address->method('setCustomerAddressId')
            ->willReturnSelf();

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['setCustomerGroupId'])
            ->onlyMethods(
                [
                    'getBillingAddress',
                    'getShippingAddress',
                    'isVirtual',
                    'collectTotals',
                ]
            )
            ->getMock();

        $quote->method('getBillingAddress')
            ->willReturn($address);
        $quote->method('getShippingAddress')
            ->willReturn($address);

        $this->sessionQuote
            ->method('getQuote')
            ->willReturn($quote);

        $orderItem = $this->createPartialMock(
            OrderItem::class,
            [
                'getParentItem',
                'getQtyOrdered',
                'getQtyShipped',
                'getQtyInvoiced',
            ]
        );
        $orderItem->method('getQtyOrdered')
            ->willReturn(2);
        $orderItem->method('getParentItem')
            ->willReturn(false);

        $iterator = new \ArrayIterator([$orderItem]);

        $itemCollectionMock = $this->getMockBuilder(ItemCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator'])
            ->getMock();
        $itemCollectionMock->method('getIterator')
            ->willReturn($iterator);

        $this->orderMock->method('getItemsCollection')
            ->willReturn($itemCollectionMock);
        $this->orderMock->method('getShippingAddress')
            ->willReturn($address);
        $this->orderMock->method('getBillingAddress')
            ->willReturn($address);
        $this->orderMock->method('getCouponCode')
            ->willReturn(true);

        $quote->expects($this->once())
            ->method('setCustomerGroupId');

        $this->adminOrderCreate->initFromOrder($this->orderMock);
    }

    /**
     *  Test case for setShippingAsBilling
     *
     * @dataProvider setShippingAsBillingDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSetShippingAsBilling(bool $flag, array $billingData, array $shippingData): void
    {
        $billingAddress = $this->createPartialMock(Address::class, ['getData']);
        $shippingAddress = $this->createPartialMock(
            Address::class,
            [
                'addData',
                'setSameAsBilling',
                'getData',
            ]
        );
        $billingAddress->expects($this->any())
            ->method('getData')
            ->willReturn($billingData);
        $shippingAddress->expects($this->any())
            ->method('getData')
            ->willReturn($shippingData);
        $shippingAddress->expects($this->any())
            ->method('addData')
            ->willReturnSelf();
        $shippingAddress->expects($this->any())
            ->method('setSameAsBilling')
            ->with($flag)
            ->willReturnSelf();
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['setRecollect'])
            ->onlyMethods(
                [
                    'getBillingAddress',
                    'getShippingAddress'
                ]
            )
            ->getMock();

        $quote->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($billingAddress);
        $quote->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddress);
        $quote->expects($this->any())
            ->method('setRecollect')
            ->willReturn(true);
        $this->sessionQuote
            ->method('getQuote')
            ->willReturn($quote);
        $this->adminOrderCreate->setShippingAsBilling($flag);
    }

    /**
     * Data provider for setShippingAsBilling function
     *
     * @return array
     */
    public static function setShippingAsBillingDataProvider(): array
    {
        return [
            'testcase when sameAsBillingFlag is false' => [
                false,
                [
                    'quote_id' => 1,
                    'entity_id' => 1,
                    'same_as_billing' => 1,
                    'customer_address_id' => null,
                    'weight' => '0.0000',
                    'free_shipping' => '0'
                ],
                [
                    'quote_id' => 1,
                    'entity_id' => 1,
                    'same_as_billing' => 1,
                    'customer_address_id' => null,
                    'weight' => '0.0000',
                    'free_shipping' => '0'
                ]
            ],
            'testcase when sameAsBillingFlag is true and there is no `weight` property' => [
                true,
                [
                    'quote_id' => 1,
                    'entity_id' => 1,
                    'same_as_billing' => 1,
                    'customer_address_id' => null,
                    'free_shipping' => '0'
                ],
                [
                    'quote_id' => 1,
                    'entity_id' => 1,
                    'same_as_billing' => 1,
                    'customer_address_id' => null,
                    'free_shipping' => '0'
                ]
            ],
            'testcase when sameAsBillingFlag is true and there is `weight` property' => [
                false,
                [
                    'quote_id' => 1,
                    'entity_id' => 1,
                    'same_as_billing' => 1,
                    'customer_address_id' => null,
                    'weight' => '0.0000',
                    'free_shipping' => '1'
                ],
                [
                    'quote_id' => 1,
                    'entity_id' => 1,
                    'same_as_billing' => 1,
                    'customer_address_id' => null,
                    'weight' => '8.0000',
                    'free_shipping' => '1'
                ]
            ]
        ];
    }
}
