<?php

namespace Monetha\Adapter\MG1;
use Monetha\Adapter\WebHookAdapterAbstract;
use Monetha\Response\Exception\ValidationException;

class WebHookAdapter extends WebHookAdapterAbstract
{
    /**
     * @var \Mage_Sales_Model_Order
     */
    private $order;

    public function __construct(\Mage_Sales_Model_Order $order)
    {
        $this->order = $order;
    }

    public function authorize()
    {
        $this->setInvoicePaid($this->order);
        $this->addOrderComment($this->order, 'Order has been successfully paid by card.');

        return true;
    }

    public function cancel($note)
    {
        if (!$this->order->canCancel()) {
            throw new ValidationException('Cannot cancel order', 400);
        }

        $this->cancelOrderInvoice($this->order);
        $this->order->cancel();
        $this->order->save();
        $this->addOrderComment($this->order, $note);

        // cannot cancel anymore in case of success
        return !$this->order->canCancel();
    }

    public function finalize()
    {
        $this->setInvoicePaid($this->order);
        $this->addOrderComment($this->order, 'Order has been successfully paid.');

        return true;
    }

    private function setInvoicePaid($order)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            $invoice->pay();
            $invoice->save();
        }
    }

    private function cancelOrderInvoice($order)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            if ($invoice->canCancel()) {
                $invoice->cancel();
                $invoice->save();
                $order->save();
            }
        }
    }

    private function addOrderComment($order, $comment)
    {
        if (!empty($comment)) {
            $order->addStatusHistoryComment($comment);
            $order->save();
        }
    }
}
