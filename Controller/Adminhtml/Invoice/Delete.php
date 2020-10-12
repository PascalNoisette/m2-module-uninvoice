<?php
namespace Netpascal\Uninvoice\Controller\Adminhtml\Invoice;

use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Grid as GridRepository;
use Magento\Framework\App\ObjectManager;

class Delete extends \Magento\Backend\App\Action
{

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var GridRepository
     */
    private $invoiceGridRepository;

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::sales_order';

    /**
     * 
     * @param Context $context 
     * @param OrderRepository $orderRepository 
     * @param GridRepository $invoiceGridRepository 
     * @param mixed #Parameter#6b5f562 
     * @return void 
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Model\OrderRepository $orderRepository
    )
    {
        $this->orderRepository = $orderRepository;
        $this->invoiceGridRepository = ObjectManager::getInstance()->get("Magento\Sales\Model\ResourceModel\Order\Invoice\Grid");
        parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->orderRepository->get($this->getRequest()->getParam('order_id'));
        $invoiceCollection = $order->getInvoiceCollection();
        $rowToPurge = [];
        foreach ($invoiceCollection as $invoice) {
            $rowToPurge[$invoice->getId()] = $invoice->getIncrementId();
            $invoice->isDeleted(true);
            $order->addRelatedObject($invoice);
        }
        foreach ([
            "state"=>"pending",
            "status"=>"pending",
            "total_paid"=>0,
            "subtotal_invoiced"=>0,
            "base_total_paid"=>0,
            "base_shipping_invoiced"=>0,
            "base_subtotal_invoiced"=>0,
            "base_total_invoiced"=>0,
            "total_invoiced"=>0,
        ] as $key => $value) {
            $order->setData($key, $value);
        }
        $items = $order->getItems();
        foreach ($items as $item) {
            foreach ([
                "qty_invoiced"=>0, 
                "discount_invoiced"=>0, 
                "base_discount_invoiced"=>0,
                "tax_invoiced"=>0,
                "base_tax_invoiced"=>0,
                "row_invoiced"=>0, 
                "base_row_invoiced"=>0
            ] as $key => $value) {
                $item->setData($key, $value);
            }
        }
        $order->save();
        $this->messageManager->addNoticeMessage(__('Order %1 restaured in pending state.', $order->getIncrementId()));
        foreach ($rowToPurge as $invoiceId => $invoiceIncrementId) {
            $this->invoiceGridRepository->purge($invoiceId);
            $this->messageManager->addNoticeMessage(__('Invoice %1 deleted.', $invoiceIncrementId));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
