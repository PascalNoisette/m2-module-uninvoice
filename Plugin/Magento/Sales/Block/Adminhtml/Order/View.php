<?php
namespace Netpascal\Uninvoice\Plugin\Magento\Sales\Block\Adminhtml\Order;

/**
 * Class View
 */
class View
{
    /**
     * URL builder
     *
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_urlBuilder;


    /**
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder
    ) {
        $this->_urlBuilder = $urlBuilder;  
    }
    /**
     * Call button
     * @param TransactionsDetail $view 
     * @return void 
     */
    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $view)
    {
        if (!$view->getOrder()->canInvoice()) {
            $view->addButton(
                'uninvoice',
                [
                    'label' => __('Uninvoice'),
                    'onclick' => 'setLocation(\'' . $view->getUrl(
                        'netpascal_uninvoice/invoice/delete',
                        [
                            'order_id' => $view->getOrderId(),
                        ]
                    ) . '\')'
                ]
            );
        }
        
        $view->addButton(
            'unrefund',
            [
                'label' => __('Unrefund'),
                'onclick' => 'setLocation(\'' . $view->getUrl(
                    'netpascal_uninvoice/creditmemo/delete',
                    [
                        'order_id' => $view->getOrderId(),
                    ]
                ) . '\')'
            ]
        );

    }
}
