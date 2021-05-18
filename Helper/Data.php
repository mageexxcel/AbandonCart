<?php
namespace Excellence\AbandonCart\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Psr\Log\LoggerInterface;
use \Magento\Framework\Mail\Template\TransportBuilder;
use \Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class Data extends AbstractHelper
{
    const MODULE_STATUS = "excellence_abandoncart/excellence_abandoncart_setting/enable_control";
    const XML_PATH_ABANDONTCART_COUPON = 'excellence_abandoncart/excellence_abandoncart_coupon/';
    const COUPON_NAME = 'coupon_name';
    const COUPON_DESC = 'coupon_desc';
    const COUPON_MAX_REDEEMTION = 'max_redeemtion';
    const COUPON_DISCOUNT_TYPE = 'discount_type';
    const COUPON_DISCOUNT_AMOUNT = 'discount_amount';
    const COUPON_SHIPPING = 'flag_is_free_shipping';
    const COUPON_CODE_LENGHT = '12';
    const ENABLE_COUPON_CODE = 'enable_coupon';
    const ENABLE_SEND_COUPON = 'send_coupon';
    const CART_ABANDON_DURATION = 'excellence_abandoncart/excellence_abandoncart_setting/set_time';
    const SECOND_ABANDON_DURATION = 'excellence_abandoncart/excellence_abandoncart_setting_cart_second/abandon_cart_hrs';
    const THIRD_ABANDON_DURATION = 'excellence_abandoncart/excellence_abandoncart_setting_cart_third/abandon_cart_hrs';
    const TEMPLATE_ID = 'excellence_abandoncart_template';
    
    protected $scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslation;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $quoteFactory;
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    protected $quoteModel;
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory
     */
    protected $dateTimeFactory;
    /**
     * @var \Magento\SalesRule\Model\Rule
     */
    protected $_cartRule;
    protected $_abandontCart;
    protected $_math;
    protected $cookie;
    protected $cookieFactory;

    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\ResourceModel\Quote $quoteModel,
        \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateTimeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        \Magento\SalesRule\Model\RuleFactory $cartRule,
        \Excellence\AbandonCart\Model\AbandontCartFactory $abandontCart,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Math\Random $mathRandom,
        CookieMetadataFactory $cookieFactory,
        CookieManagerInterface $cookie,
        \Magento\Integration\Model\Oauth\TokenFactory $tokenManagementFactory
    ){
        $this->_cookiesData = $cookie;
        // $this->_pushHelper = $helperData;
        // $this->_templateFactory = $templateFactory;
        // $this->_notificationFactory = $notificationFactory;
        // $this->_notification = $notifiation;
        $this->_tokenManagement = $tokenManagementFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
        $this->quoteModel=$quoteModel;
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
        $this->_transportBuilder = $transportBuilder;
        $this->_inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->_cartRule = $cartRule;
        $this->_abandontCart = $abandontCart;
        $this->_customerSession = $customerSession;
        $this->_math = $mathRandom;
    }

    public function verifyingTokenInDb($customerId,$customerEmail){
        try{
            /**
             * validating cookie from pushnotification_abandoncart table
             */
            $token = $this->_cookiesData->getCookie('token');
            $tokenCollection = $this->_tokenManagement->create()
                            ->getCollection()
                            ->addFieldToFilter("token",["eq"=>$token]);
            if(count($tokenCollection) == 0){
                /**
                 * current cookie not found in pushnotification_abandoncart table
                 * inserting it into pushnotification_abandoncart before proceding
                 */
                $data = array(
                    'token' => $token,
                    'userId' => $customerId,
                    'userEmail' => $customerEmail
                );
                $tokenData = $this->_tokenManagement->create();
                $tokenData->setData($data);
                $tokenData->save();
            }
        }catch(\Exception $e){
            die($e);
        }
    }
    public function getAllActiveQuoteItems(){
        $isEnable = $this->scopeConfig->getValue(self::MODULE_STATUS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $CustomerData;
        if($isEnable){
            try{
                $firstCartDuration = $this->scopeConfig->getValue(self::CART_ABANDON_DURATION, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                $secondCartDuration = $this->scopeConfig->getValue(self::SECOND_ABANDON_DURATION, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                $thirdCartDuration = $this->scopeConfig->getValue(self::THIRD_ABANDON_DURATION, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

                $quotes = $this->quoteFactory->create()->getCollection()
                        ->addFieldToFilter('customer_email',['neq' => 'NULL'])
                        ->addFieldToFilter('is_active',1);
                        // echo '<pre>';
                        // print_r($quotes->getData()); die('rr');
                $quoteLastActivity = "";
                foreach($quotes as $quote){
                    $timeStampDiff = (strtotime($this->getFormattedDate())-strtotime($quote->getData('updated_at')));
                    $quoteLastActivity =  strtotime($quote->getData('updated_at'));
                    $diffresult = $this->dateDiff(strtotime($this->getFormattedDate()),$quoteLastActivity);
                    // print_r($diffresult); die("gg");
                    // Get Data From Table 'excellence_abandontcart_main' by using CustomerId From Quote
                    $customerQuoteData = $this->CouponCodeCollectionByCustomerId($quote->getCustomerId());
                                        // print_r($customerQuoteData); die("gg");

                    if(($diffresult['hours'] > 3 || $diffresult['days']>0)){
                        $customerMail = $quote->getcustomerEmail();
                        $customerFirstName = $quote->getcustomerFirstname();
                        $customerLastName = $quote->getcustomerLastname();
                        $customerId = $quote->getCustomerGroupId();
                        $quoteId = $quote->getEntityId();
                        // Validate Data to check if customer exit in our table excellence_abandontcart_main else create Customer Data First
                        if((count($customerQuoteData->getData()) <= 0)){
                            $customerQuoteData = $this->saveCustomerData($quote);
                            // Customer Data Entered In Our Table
                        }
                        // To Create Random Coupon For Each Customer
                        if($customerQuoteData->getIsMailSent() < 2){
                            $couponCode = $this->createCartRuleCoupon($quote, $customerQuoteData);
                        }
                        // Sending Mail to Each Customer
                        if($customerQuoteData->getIsMailSent() < 2){
                            $this->sendEmailViaCronJob($customerMail,$customerFirstName,$customerLastName,$quoteId, $couponCode);
                        }
                    }
                }
            }catch(\Exception $e){
                $this->_logger->info('Excellence_AbandonCart : Something Went wrong !');
            }
        }
    }
    /**
     * @param quote id
     * @return array of items into cart 
     */
    public function loadQuoteById($quoteId){
        $quote = $this->quoteRepository->get($quoteId);
        $items = $quote->getAllItems();
        return $items;
    }
    /**
     * @param string length of coupon and any specified character
     * @return string alphanumeric coupon code of specified length 
     */
    public function generateCouponCode($length,  $chars = null){
        return $this->_math->getRandomString($length, $chars);
    }
    /**
     * @param string Quote Data and CustomerData If Exist in our Table excellence_abandontcart_main
     * @return string Coupon Data If Created 
     */
    public function createCartRuleCoupon($quote, $customerQuoteData){
        if(($this->getGeneralConfig(self::XML_PATH_ABANDONTCART_COUPON, self::ENABLE_COUPON_CODE)) && ($customerQuoteData->getIsMailSent() == $this->getGeneralConfig(self::XML_PATH_ABANDONTCART_COUPON, self::ENABLE_SEND_COUPON)) && !$customerQuoteData->getIsCouponSent()){
            $str_result = $this->generateCouponCode(self::COUPON_CODE_LENGHT);
            $ranCode = substr(str_shuffle($str_result), 0, 8); 
            $ranStr = substr(str_shuffle($str_result), 0, 5); 
            $coupon['name'] = $this->getGeneralConfig(self::XML_PATH_ABANDONTCART_COUPON, self::COUPON_NAME).'-'.$ranStr.''; //Generate a rule name
            $coupon['desc'] = $this->getGeneralConfig(self::XML_PATH_ABANDONTCART_COUPON, self::COUPON_DESC);
            $coupon['start'] = date('Y-m-d'); //Coupon use start date
            $coupon['end'] = ''; //coupon use end date
            $coupon['max_redemptions'] = $this->getGeneralConfig(self::XML_PATH_ABANDONTCART_COUPON, self::COUPON_MAX_REDEEMTION); //Uses per Customer
            $coupon['discount_type'] = $this->getGeneralConfig(self::XML_PATH_ABANDONTCART_COUPON, self::COUPON_DISCOUNT_TYPE); //for discount type
            $coupon['discount_amount'] = $this->getGeneralConfig(self::XML_PATH_ABANDONTCART_COUPON, self::COUPON_DISCOUNT_AMOUNT); //discount amount/percentage
            $coupon['flag_is_free_shipping'] = $this->getGeneralConfig(self::XML_PATH_ABANDONTCART_COUPON, self::COUPON_SHIPPING);
            $coupon['redemptions'] = 1;
            $coupon['code'] = $ranCode; //generate a random coupon code

            $shoppingCartPriceRule = $this->_cartRule->create();
            $shoppingCartPriceRule->setName($coupon['name'])
            ->setDescription($coupon['desc'])
            ->setFromDate($coupon['start'])
            ->setToDate($coupon['end'])
            ->setUsesPerCustomer($coupon['max_redemptions'])
            ->setCustomerGroupIds(array('1','2','3')) //select customer group
            ->setIsActive(1)
            ->setSimpleAction($coupon['discount_type'])
            ->setDiscountAmount($coupon['discount_amount'])
            ->setDiscountQty(1)
            ->setApplyToShipping($coupon['flag_is_free_shipping'])
            ->setTimesUsed($coupon['redemptions'])
            ->setWebsiteIds(array('1'))
            ->setCouponType(2)
            ->setCouponCode($coupon['code'])
            ->setUsesPerCoupon(1);
            $shoppingCartPriceRule->save();
            $this->updateCustomerData($shoppingCartPriceRule->getCouponCode(), $customerQuoteData->getCustomerId());
        }else{
            $shoppingCartPriceRule = null;
            $coupon = null;
            $this->updateCustomerData($coupon, $customerQuoteData->getCustomerId());
        }
        return $shoppingCartPriceRule;
    }
    /**
     * @param string Coupon Code and CustomerId
     */
    public function updateCustomerData($coupon = null, $customerId){
        if($customerId){
            $abandontCartData = $this->_abandontCart->create();
            $abandontCartData->load($customerId, "customer_id");
            $mailSentCount = $abandontCartData->getIsMailSent();
            $mailSentCount++;
            if($coupon != null){
                $abandontCartData->setCouponCode($coupon);
                $abandontCartData->setExpiryTime(24);
                $abandontCartData->setStatus(1);
                $abandontCartData->setIsMailSent($mailSentCount);
                $abandontCartData->setIsCouponSent(1);
            }else{
                $abandontCartData->setIsMailSent($mailSentCount);
            }
            $abandontCartData->save();
        }
    }
    /**
     * @param string Quote Data To create/Save Customer 
     */
    public function saveCustomerData($quote){
        $abandontCartData = $this->_abandontCart->create();
        $collectionData['customer_id'] = $quote->getCustomerId();
        $collectionData['customer_email']= $quote->getCustomerEmail();
        $collectionData['customer_group_id']= $quote->getCustomerGroupId();
        $collectionData['quote_id'] = $quote->getEntityId();
        $collectionData['Status']= 0;
        $collectionData['is_mail_sent']= 0;
        $abandontCartData->setData($collectionData);
        $abandontCartData->save();
        return $abandontCartData;
    }
    /**
     * @return array of date difference
     */
    public function dateDiff($date1,$date2){
        $diff = abs($date1 - $date2);
            
            // To get the year divide the resultant date into 
            // total seconds in a year (365*60*60*24) 
            $years = floor($diff / (365*60*60*24));  
            
            
            // To get the month, subtract it with years and 
            // divide the resultant date into 
            // total seconds in a month (30*60*60*24) 
            $months = floor(($diff - $years * 365*60*60*24) 
                                        / (30*60*60*24));  
            
            
            // To get the day, subtract it with years and  
            // months and divide the resultant date into 
            // total seconds in a days (60*60*24) 
            $days = floor(($diff - $years * 365*60*60*24 -  
                        $months*30*60*60*24)/ (60*60*24)); 
            
            
            // To get the hour, subtract it with years,  
            // months & seconds and divide the resultant 
            // date into total seconds in a hours (60*60) 
            $hours = floor(($diff - $years * 365*60*60*24  
                - $months*30*60*60*24 - $days*60*60*24) 
                                            / (60*60));  
            
            
            // To get the minutes, subtract it with years, 
            // months, seconds and hours and divide the  
            // resultant date into total seconds i.e. 60 
            $minutes = floor(($diff - $years * 365*60*60*24  
                    - $months*30*60*60*24 - $days*60*60*24  
                                    - $hours*60*60)/ 60);  
            
            
            // To get the minutes, subtract it with years, 
            // months, seconds, hours and minutes  
            $seconds = floor(($diff - $years * 365*60*60*24  
                    - $months*30*60*60*24 - $days*60*60*24 
                            - $hours*60*60 - $minutes*60));

            $dateDifference = array(
                "years" => $years,
                "months" => $months,
                "days" => $days,
                "hours" => $hours,
                "mins" => $minutes,
                "secs" => $seconds
            );
            return $dateDifference;
    }
    /**
     * @return GMT format of current date time 
     */
    public function getFormattedDate(){
        $dateModel = $this->dateTimeFactory->create();
        return $dateModel->gmtDate();
    }
    /**
     * @return array Collection Data according to CustomerId
     */
    public function CouponCodeCollectionByCustomerId($customerId){
        return $this->_abandontCart->create()->getCollection()->addFieldToFilter('customer_id', $customerId)->getFirstItem();
    }
    /**
     * @return void this method send email to all active cart which last updated 3 hrs ago or more
     */
    public function sendEmailViaCronJob($customerMail,$customerFirstName,$customerLastName,$quoteId, $couponDetails = null){
        try {

            $templateId = self::TEMPLATE_ID;
            $storeName = $this->_storeManager->getStore()->getName();
            $senderEmail = $this->scopeConfig->getValue('trans_email/ident_support/email',ScopeInterface::SCOPE_STORE);    
            $toMail = $customerMail;
            $senderName = $storeName;
            $receiverName = $customerFirstName." ".$customerLastName;
            
            $from = array('email' => (string) $senderEmail, 'name' => (string) $senderName);
            $this->_inlineTranslation->suspend();
            $transport = $this->_transportBuilder
            ->setTemplateIdentifier($templateId)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                ]
            )
            ->setTemplateVars([
                'subject' => 'You forgot something in your cart',
                'customerEmail' => $toMail,
                'customerName' => $receiverName,                        
                'senderName' => $senderName,
                'senderEmail' => $senderEmail,
                'quoteId' => $quoteId,
                'couponCode' => ($couponDetails != null) ? $couponDetails->getCouponCode() :''
            ])
            ->setFrom($from)
            ->addTo($toMail, $receiverName)
            ->getTransport();
            $transport->sendMessage();
            $this->_inlineTranslation->resume();
        
            $this->_logger->info('');
            
        } catch (Exception $ex) {
            $this->_logger->info('Problem While Sending Mail Via Cron !');
            echo $ex->getMessage();
        }
    }
    /**
     * @return void this method send email to all active cart which last updated 3 hrs ago or more
     */
    public function getConfigValue($field,$storeId = null){
		return $this->scopeConfig->getValue(
			$field, ScopeInterface::SCOPE_STORE, $storeId
		);
	}
	public function getGeneralConfig($path, $code, $storeId = null)
	{
		return $this->getConfigValue($path. $code, $storeId);
    }
    /**
     * @return Boolen Validating Coupon Code If Exist in Our Table excellence_abandontcart_main
     */
    // Validating Of Coupon Code If Exist in Our Module...
	public function validateCouponCode($couponCode){
        $customerId = $this->_customerSession->getCustomer()->getId();
        $couponData = $this->_abandontCart->create()->getCollection()->addFieldToFilter('coupon_code',$couponCode)->addFieldToFilter('status',1);
		if(count($couponData)>0){
			return true;
		}return false;
		
    }
    /**
     * @return Boolen Validating Coupon Code To Unique Customer If Exist in Our Table excellence_abandontcart_main
     */
	public function validCustomer($couponCode){
		$customerId = $this->_customerSession->getCustomer()->getId();
		$couponData = $this->_abandontCart->create()->getCollection()->addFieldToFilter('coupon_code',$couponCode)->addFieldToFilter('status',1)->addFieldToFilter('customer_id',
			array('like' => '%'.$customerId.'%'));
		if(count($couponData)>0){
			return true;
		}return false;
	}
}
?>