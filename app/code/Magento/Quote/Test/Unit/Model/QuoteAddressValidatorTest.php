<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Quote\Test\Unit\Model;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteAddressValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteAddressValidatorTest extends TestCase
{
    /**
     * @var QuoteAddressValidator
     */
    private $validator;

    /**
     * @var AddressRepositoryInterface|MockObject
     */
    private $addressRepositoryMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var Session|MockObject
     */
    private $customerSessionMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->addressRepositoryMock = $this->getMockBuilder(AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new QuoteAddressValidator(
            $this->addressRepositoryMock,
            $this->customerRepositoryMock,
            $this->customerSessionMock
        );
    }

    /**
     * Test that validation uses customer ID when available, regardless of is_guest flag
     *
     * This tests the fix for the issue where is_guest flag and customer_id are out of sync
     */
    public function testValidateForCartWithCustomerIdIgnoresGuestFlag()
    {
        $customerId = 123;
        $customerAddressId = 456;

        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->getMock();

        $addressMock = $this->getMockBuilder(AddressInterface::class)
            ->getMock();

        $customerAddressMock = $this->getMockBuilder(CustomerAddressInterface::class)
            ->getMock();

        // Set up cart to have a customer ID
        $cartMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        // Set up address with customer address ID
        $addressMock->expects($this->once())
            ->method('getCustomerAddressId')
            ->willReturn($customerAddressId);

        // Mock customer repository to return customer with addresses
        $customerMock->expects($this->once())
            ->method('getAddresses')
            ->willReturn([$customerAddressMock]);

        $customerAddressMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerAddressId);

        $this->customerRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->addressRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($customerAddressId)
            ->willReturn($customerAddressMock);

        // Execute validation - should not throw exception
        $this->validator->validateForCart($cartMock, $addressMock);
    }

    /**
     * Test that validation correctly handles guest cart (no customer ID)
     */
    public function testValidateForCartWithGuestCart()
    {
        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $addressMock = $this->getMockBuilder(AddressInterface::class)
            ->getMock();

        // Set up cart without customer ID (guest)
        $cartMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(null);

        // Address should not have customer address ID for guest
        $addressMock->expects($this->once())
            ->method('getCustomerAddressId')
            ->willReturn(null);

        // Execute validation - should not throw exception
        $this->validator->validateForCart($cartMock, $addressMock);
    }

    /**
     * Test that validation throws exception when guest cart has customer address ID
     */
    public function testValidateForCartThrowsExceptionWhenGuestHasCustomerAddress()
    {
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Invalid customer address id 456');

        $customerAddressId = 456;

        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $addressMock = $this->getMockBuilder(AddressInterface::class)
            ->getMock();

        // Set up cart without customer ID (guest)
        $cartMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(null);

        // Address has customer address ID even though cart is guest
        $addressMock->expects($this->once())
            ->method('getCustomerAddressId')
            ->willReturn($customerAddressId);

        // Execute validation - should throw exception
        $this->validator->validateForCart($cartMock, $addressMock);
    }

    /**
     * Test that validation throws exception when customer address doesn't belong to customer
     */
    public function testValidateForCartThrowsExceptionWhenAddressDoesNotBelongToCustomer()
    {
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Invalid customer address id 456');

        $customerId = 123;
        $customerAddressId = 456;
        $differentAddressId = 789;

        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->getMock();

        $addressMock = $this->getMockBuilder(AddressInterface::class)
            ->getMock();

        $customerAddressMock = $this->getMockBuilder(CustomerAddressInterface::class)
            ->getMock();

        // Set up cart with customer ID
        $cartMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        // Address has customer address ID
        $addressMock->expects($this->once())
            ->method('getCustomerAddressId')
            ->willReturn($customerAddressId);

        // Mock customer with different address
        $customerMock->expects($this->once())
            ->method('getAddresses')
            ->willReturn([$customerAddressMock]);

        $customerAddressMock->expects($this->once())
            ->method('getId')
            ->willReturn($differentAddressId);

        $this->customerRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->addressRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($customerAddressId)
            ->willReturn($customerAddressMock);

        // Execute validation - should throw exception
        $this->validator->validateForCart($cartMock, $addressMock);
    }
}
