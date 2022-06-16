<?php

namespace Drupal\gv_fanatics_plus_product;

class Product {
	public $ProductBookingCode;
	public $IDProduct;
	public $ProductCode;
	public $ProductName;
	public $ProductDescription;
	public $IDServiceType;
	public $IDCurrency;
	public $Price;
	public $Themes;
	
	/**
	 * @var ProductSeasonPassData
	 */
	public $SeasonPassData;
}

class ProductSeasonPassData {
	public $ValidityFrom;
	public $ValidityTo;
	public $AgeFrom;
	public $AgeTo;
	public $Offer;
	public $ReferencePrice;
	public $Order;
	public $PreviousBooking;
}
