<?php
namespace AzureKai\Exports\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Sales\Model\Order;

class ExportOrders extends Command
{
	protected $orderCollectionFactory;
	protected $datetime;
	
	public function __construct(
	\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime
	) {
		$this->orderCollectionFactory = $orderCollectionFactory;
        $this->datetime = $datetime;
		parent::__construct();
	}
	
   protected function configure()
   {
       $this->setName('export:orders');
       $this->setDescription('Export recent orders. If input date provided, export any after provided date');
       $this->addArgument('from_date', InputArgument::OPTIONAL, 'Optional earliest date for orders to be exported');
	   parent::configure();
   }
   protected function execute(InputInterface $input, OutputInterface $output)
   {
	   $fromDate = $input->getArgument('from_date');
	   //If there is an argument, use it
		if($fromDate)
		{
			$timeStamp = strtotime($fromDate);
			//No valid timestamp -> output help and quit command
			if(!$timeStamp)
			{
				$output->writeln("Input was not a date. Please enter a date in format: DD-MM-YYYY. Append time directly to argument if desired");
				return;
			}
		}
		else
		{			
			//Get magento's time and subtract one day
			//Important note: magento time defaults to UTC
			$timeStamp = $this->datetime->gmtTimeStamp();
			$timeStamp = strtotime(' - 1day'. strtotime($timeStamp));
		}
		
		$timeString = date("Y-m-d H:i:s", $timeStamp);
		
		//Setup the query
		$this->orders = $this->orderCollectionFactory->create();
		$this->orders->addFieldToSelect("entity_id");
		$this->orders->addFieldToSelect("customer_firstname");
		$this->orders->addFieldToSelect("customer_middlename");
		$this->orders->addFieldToSelect("customer_lastname");
		$this->orders->addFieldToSelect("created_at");
		$this->orders->addFieldToSelect("grand_total");
		$this->orders->addFieldToSelect("tax_amount");
		//Get all orders for which status == processing
		$this->orders->addFieldToFilter("status", "pending");
		//And where date of creation >= presented timestamp
		$this->orders->addFieldToFilter("created_at", ['gteq' => $timeString]);
		$this->orders->load();
		
		
		//Open the file to prepare writing. If the directory does not exist, make it
		//Note: currently saves the directory directly in the bin file, would ask customer what is preferred (relative or defined path in file)
		//Note: overwrites any order already existing that is made on the same date
		if(!file_exists("OrderExports"))
			mkdir("OrderExports");
		$fileName = "OrderExports/orders_" . date("Y-m-d") . ".csv";
		$file = fopen($fileName, "w");
		
		//Write column names
		fwrite($file, "Order ID, Voornaam, Achternaam, Straat, Postcode, Stad, Land, Totaal, BTW \n");
		
		foreach($this->orders as $order)
		{
			//Get the billing address and write all relevant data
			$address =  $order->getBillingAddress();
			
			$body = sprintf("%s,%s,%s,%s,%s,%s,%s,%s,%s\n", $order->getEntityID(), $order->getCustomerFirstName(), 
				$order->getCustomerMiddleName() . " " . $order->getCustomerLastName(), $address->getStreet()[0],
				$address->getPostCode(), $address->getCity(), $address->getCountryID(),
				$order->getGrandTotal(), $order->getTaxAmount());
			fwrite($file, $body);
		}
		
		//Cleanup
		$output->writeln("Successfully exported!");
		fclose($file);				
   }
}
?>