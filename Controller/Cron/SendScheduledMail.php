<?php
namespace Excellence\AbandonCart\Controller\Cron;

use \Psr\Log\LoggerInterface;

class SendScheduledMail extends \Magento\Framework\App\Action\Action
{
  const XML_PATH_ABANDONTCART = 'excellence_abandoncart/excellence_abandoncart_setting/';
  /**
   * @var \Magento\Framework\View\Result\PageFactory
   */
  protected $_pageFactory;
  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $_logger;
  /**
   * @var \Excellence\GiftCard\Helper\Data
   */
  protected $_dataHelper;

  protected $_cartRule;

  public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Magento\Framework\View\Result\PageFactory $pageFactory,
    LoggerInterface $logger,
    \Excellence\AbandonCart\Helper\Data $dataHelper
  ){
    $this->_pageFactory = $pageFactory;
    $this->_logger = $logger;
    $this->_dataHelper = $dataHelper;
    return parent::__construct($context);
  }

  public function execute(){
    if($this->_dataHelper->getGeneralConfig(self::XML_PATH_ABANDONTCART, 'enable_control')){
      $this->_dataHelper->getAllActiveQuoteItems();
    }
    return $this->_pageFactory->create();
  }
}