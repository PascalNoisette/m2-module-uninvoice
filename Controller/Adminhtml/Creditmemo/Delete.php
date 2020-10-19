<?php
namespace Netpascal\Uninvoice\Controller\Adminhtml\Creditmemo;

use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Grid as GridRepository;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\CreditmemoRepositoryInterface;

class Delete extends \Magento\Backend\App\Action
{

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;


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
     * @param GridRepository $creditmemoGridRepository 
     * @param mixed #Parameter#6b5f562 
     * @return void 
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
    )
    {
        $this->orderRepository = $orderRepository;
        $this->transactionRepository = $transactionRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->orderRepository->get($this->getRequest()->getParam('order_id'));
        $creditmemoCollection = $order->getCreditmemosCollection();
        foreach ($creditmemoCollection as $creditmemo) {
            $creditmemo->isDeleted(true);
            $order->addRelatedObject($creditmemo);
        }
        $invoiceCollection = $order->getInvoiceCollection();
        foreach ($invoiceCollection as $invoice) {
            $invoice->setData("is_used_for_refund", false);
            $invoice->setData("base_total_refunded", 0);
            $order->addRelatedObject($invoice);
        }
        foreach ([
            "state"=>"processing",
            "status"=>"processing",
            "base_subtotal_refunded"=>0,
            "subtotal_refunded"=>0,
            "base_total_online_refunded"=>0,
            "total_online_refunded"=>0,
            "base_total_refunded"=>0,
            "total_refunded"=>0,
            "base_shipping_refunded"=>0,
        ] as $key => $value) {
            $order->setData($key, $value);
        }
        $items = $order->getItems();
        foreach ($items as $item) {
            foreach ([
                "qty_refunded"=>0,
                "amount_refunded"=>0,
                "base_amount_refunded"=>0,
                "tax_refunded"=>0,
            ] as $key => $value) {
                $item->setData($key, $value);
            }
        }
        $transaction = $this->transactionRepository->getByTransactionType(
            \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH,
            $order->getPayment()->getId(),
            $order->getId()
        );

        $order->getPayment()->setData("refunded_amount", 0);
        $childrenPayments = $order->getPayment()->getExtensionAttributes()->getChildrenPayments();
        foreach($childrenPayments as $payment) {
            $payment->setData("refunded_amount", 0);
            $order->addRelatedObject($payment);
        }

        $transaction->setIsClosed(false);
        $order->addRelatedObject($transaction);
        $order->save();
        $this->messageManager->addNoticeMessage(__('Order %1 restaured in processing state.', $order->getIncrementId()));
        
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
