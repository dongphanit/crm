<?php

namespace OroCRM\Bundle\MagentoBundle\Tests\Unit\Importexport\Strategy;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\MagentoBundle\Entity\Cart;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;
use OroCRM\Bundle\MagentoBundle\ImportExport\Strategy\CartWithExistingCustomerStrategy;

class CartWithExistingCustomerStrategyTest extends AbstractExistingCustomerStrategyTest
{
    /**
     * @return CartWithExistingCustomerStrategy
     */
    protected function getStrategy()
    {
        return new CartWithExistingCustomerStrategy(
            $this->eventDispatcher,
            $this->strategyHelper,
            $this->fieldHelper,
            $this->databaseHelper
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Execution context is not configured
     */
    public function testProcessFailed()
    {
        $customer = new Customer();
        $customer->setOriginId(1);
        $channel = new Channel();
        $cart = new Cart();
        $cart->setCustomer($customer);
        $cart->setChannel($channel);

        $this->assertNull($this->getStrategy()->process($cart));
    }

    public function testProcess()
    {
        $customer = new Customer();
        $customer->setOriginId(1);
        $channel = new Channel();
        $cart = new Cart();
        $cart->setCustomer($customer);
        $cart->setChannel($channel);

        $strategy = $this->getStrategy();

        $execution = $this->getMock('Akeneo\Bundle\BatchBundle\Item\ExecutionContext');
        $this->jobExecution->expects($this->any())->method('getExecutionContext')
            ->will($this->returnValue($execution));
        $strategy->setStepExecution($this->stepExecution);

        $cartItem = ['customerId' => uniqid()];
        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextInterface $context */
        $context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $context->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($cartItem));
        $strategy->setImportExportContext($context);

        $execution->expects($this->exactly(2))
            ->method('get')
            ->with($this->isType('string'));
        $execution->expects($this->exactly(2))
            ->method('put')
            ->with($this->isType('string'), $this->isType('array'));

        $this->assertNull($strategy->process($cart));
    }
}
