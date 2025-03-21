<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Customer\Controller\Account;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Delegation\Storage as DelegatedStorage;
use Magento\Framework\App\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Theme\Controller\Result\MessagePlugin;
use Symfony\Component\Mime\Test\Constraint\EmailTextBodyContains;

/**
 * Tests from customer account create post action.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreatePostTest extends AbstractController
{
    /**
     * @var TransportBuilderMock
     */
    private $transportBuilderMock;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->transportBuilderMock = $this->_objectManager->get(TransportBuilderMock::class);
        $this->storeManager = $this->_objectManager->get(StoreManagerInterface::class);
        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        $this->customerRegistry = $this->_objectManager->get(CustomerRegistry::class);
        $this->cookieManager = $this->_objectManager->get(CookieManagerInterface::class);
        $this->urlBuilder = $this->_objectManager->get(UrlInterface::class);
    }

    /**
     * Tests that without form key user account won't be created
     * and user will be redirected on account creation page again.
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @return void
     */
    public function testNoFormKeyCreatePostAction(): void
    {
        $this->fillRequestWithAccountData('test1@email.com');
        $this->getRequest()->setPostValue('form_key', null);
        $this->dispatch('customer/account/createPost');

        $this->assertCustomerNotExists('test1@email.com');
        $this->assertRedirect($this->stringEndsWith('customer/account/create/'));
        $this->assertSessionMessages(
            $this->stringContains((string)__('Invalid Form Key. Please refresh the page.')),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Check that DateTime option is NOT changed after creating Customer account for which guest order was placed.
     *
     * @return void
     * @magentoDataFixture Magento/Sales/_files/guest_order_with_product_and_custom_options.php
     */
    public function testCreateCustomerAccountAfterIssuingGuestOrder(): void
    {
        /** @var Order $order */
        $order = $this->_objectManager->create(Order::class);
        $order->loadByIncrementId('100000001');

        /** @var CustomerInterface $customer */
        $customer = $this->_objectManager->create(CustomerInterface::class);

        /** @var DelegatedStorage $delegatedStorage */
        $delegatedStorage = $this->_objectManager->get(DelegatedStorage::class);
        $delegatedStorage->storeNewOperation($customer, ['__sales_assign_order_id' => $order->getId()]);

        $this->fillRequestWithAccountData('customer@example.com');
        $this->dispatch('customer/account/createPost');

        $this->assertRedirect($this->stringEndsWith('customer/account/'));
        $this->assertSessionMessages(
            $this->containsEqual(
                (string)__('Thank you for registering with %1.', $this->storeManager->getStore()->getFrontendName())
            ),
            MessageInterface::TYPE_SUCCESS
        );

        $expectedResult = [
            'year' => '2021',
            'month' => '9',
            'day' => '9',
            'hour' => '2',
            'minute' => '2',
            'day_part' => 'am',
            'date_internal' => '2021-09-09 02:02:00',
        ];
        /** @var OrderItem $orderItem */
        $orderItem = $order->getItemsCollection()->getFirstItem();
        $actualResult = current($orderItem->getBuyRequest()->getOptions());
        $this->assertIsArray($actualResult);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_website customer/create_account/confirm 0
     * @magentoConfigFixture current_store customer/create_account/default_group 1
     * @magentoConfigFixture current_store customer/create_account/generate_human_friendly_id 0
     *
     * @return void
     */
    public function testNoConfirmCreatePostAction(): void
    {
        $this->fillRequestWithAccountData('test1@email.com');
        $this->dispatch('customer/account/createPost');
        $this->assertRedirect($this->stringEndsWith('customer/account/'));
        $this->assertSessionMessages(
            $this->containsEqual(
                (string)__('Thank you for registering with %1.', $this->storeManager->getStore()->getFrontendName())
            ),
            MessageInterface::TYPE_SUCCESS
        );
        $customer = $this->customerRegistry->retrieveByEmail('test1@email.com');
        //Assert customer group
        $this->assertEquals(1, $customer->getDataModel()->getGroupId());
        //Assert customer increment id generation
        $this->assertNull($customer->getData('increment_id'));
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_website customer/create_account/confirm 0
     * @magentoConfigFixture current_store customer/create_account/default_group 2
     * @magentoConfigFixture current_store customer/create_account/generate_human_friendly_id 1
     * @return void
     */
    public function testCreatePostWithCustomConfiguration(): void
    {
        $this->fillRequestWithAccountData('test@email.com');
        $this->dispatch('customer/account/createPost');
        $this->assertRedirect($this->stringEndsWith('customer/account/'));
        $this->assertSessionMessages(
            $this->containsEqual(
                (string)__('Thank you for registering with %1.', $this->storeManager->getStore()->getFrontendName())
            ),
            MessageInterface::TYPE_SUCCESS
        );
        $customer = $this->customerRegistry->retrieveByEmail('test@email.com');
        //Assert customer group
        $this->assertEquals(2, $customer->getDataModel()->getGroupId());
        //Assert customer increment id generation
        $this->assertNotNull($customer->getData('increment_id'));
        $this->assertMatchesRegularExpression('/\d{8}/', $customer->getData('increment_id'));
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_website customer/create_account/confirm 1
     *
     * @return void
     */
    public function testWithConfirmCreatePostAction(): void
    {
        $email = 'test2@email.com';
        $this->fillRequestWithAccountData($email);
        $this->dispatch('customer/account/createPost');
        $this->assertRedirect($this->stringContains('customer/account/index/'));
        $message = 'You must confirm your account.'
            . ' Please check your email for the confirmation link or <a href="%1">click here</a> for a new link.';
        $url = $this->urlBuilder->getUrl('customer/account/confirmation', ['_query' => ['email' => $email]]);
        $this->assertSessionMessages(
            $this->containsEqual((string)__($message, $url)),
            MessageInterface::TYPE_SUCCESS
        );
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     *
     * @return void
     */
    public function testExistingEmailCreatePostAction(): void
    {
        $this->fillRequestWithAccountData('customer@example.com');
        $this->dispatch('customer/account/createPost');
        $this->assertRedirect($this->stringContains('customer/account/create/'));
        $message = 'There is already an account with this email address.'
            . ' If you are sure that it is your email address, <a href="%1">click here</a> '
            . 'to get your password and access your account.';
        $url = $this->urlBuilder->getUrl('customer/account/forgotpassword');
        $this->assertSessionMessages($this->containsEqual((string)__($message, $url)), MessageInterface::TYPE_ERROR);
    }

    /**
     * Register Customer with email confirmation.
     *
     * @magentoAppArea frontend
     * @magentoConfigFixture current_website customer/create_account/confirm 1
     *
     * @return void
     */
    public function testRegisterCustomerWithEmailConfirmation(): void
    {
        $email = 'test_example@email.com';
        $this->fillRequestWithAccountData($email);
        $this->dispatch('customer/account/createPost');
        $this->assertRedirect($this->stringContains('customer/account/index/'));
        $message = 'You must confirm your account.'
            . ' Please check your email for the confirmation link or <a href="%1">click here</a> for a new link.';
        $url = $this->urlBuilder->getUrl('customer/account/confirmation', ['_query' => ['email' => $email]]);

        $this->assertSessionMessages($this->containsEqual((string)__($message, $url)), MessageInterface::TYPE_SUCCESS);
        /** @var CustomerInterface $customer */
        $customer = $this->customerRepository->get($email);
        $confirmation = $customer->getConfirmation();
        $sendMessage = $this->transportBuilderMock->getSentMessage();
        $this->assertNotNull($sendMessage);
        $rawMessage = quoted_printable_decode($sendMessage->getBody()->bodyToString());

        $this->assertStringContainsString(
            sprintf(
                'customer/account/confirm/?email=%s&amp;id=%s&amp;key=%s',
                urlencode($customer->getEmail()),
                $customer->getId(),
                $confirmation
            ),
            $rawMessage
        );
        $this->resetRequest();
        $this->getRequest()
            ->setParam('id', $customer->getId())
            ->setParam('key', $confirmation);
        $this->dispatch('customer/account/confirm');
        $this->assertRedirect($this->stringContains('customer/account/index/'));
        $this->assertSessionMessages(
            $this->containsEqual(
                (string)__('Thank you for registering with %1.', $this->storeManager->getStore()->getFrontendName())
            ),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertEmpty($this->customerRepository->get($email)->getConfirmation());
    }

    /**
     * Fills request with customer data.
     *
     * @param string $email
     * @return void
     */
    private function fillRequestWithAccountData(string $email): void
    {
        $this->getRequest()
            ->setMethod(HttpRequest::METHOD_POST)
            ->setParam(CustomerInterface::FIRSTNAME, 'firstname1')
            ->setParam(CustomerInterface::LASTNAME, 'lastname1')
            ->setParam(CustomerInterface::EMAIL, $email)
            ->setParam('password', '_Password1')
            ->setParam('password_confirmation', '_Password1')
            ->setParam('telephone', '5123334444')
            ->setParam('street', ['1234 fake street', ''])
            ->setParam('city', 'Austin')
            ->setParam('postcode', '78701')
            ->setParam('country_id', 'US')
            ->setParam('default_billing', '1')
            ->setParam('default_shipping', '1')
            ->setParam('is_subscribed', '0')
            ->setPostValue('create_address', true);
    }

    /**
     * Asserts that customer does not exists.
     *
     * @param string $email
     * @return void
     */
    private function assertCustomerNotExists(string $email): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage(
            (string)__(
                'No such entity with %fieldName = %fieldValue, %field2Name = %field2Value',
                [
                    'fieldName' => 'email',
                    'fieldValue' => $email,
                    'field2Name' => 'websiteId',
                    'field2Value' => 1
                ]
            )
        );
        $this->assertNull($this->customerRepository->get($email));
    }

    /**
     * Clears request.
     *
     * @return void
     */
    protected function resetRequest(): void
    {
        parent::resetRequest();
        $this->cookieManager->deleteCookie(MessagePlugin::MESSAGES_COOKIES_NAME);
        $this->_objectManager->removeSharedInstance(Http::class);
        $this->_objectManager->removeSharedInstance(Request::class);
    }
}
