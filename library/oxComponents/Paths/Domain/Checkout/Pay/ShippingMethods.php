<?php
/**
 * #PHPHEADER_OXID_LICENSE_INFORMATION#
 */

namespace OxidEsales\TestingLibrary\oxComponents\Paths\Domain\Checkout\Pay;

use OxidEsales\TestingLibrary\oxComponents\Paths\Path;

class ShippingMethods extends Path
{
    /**
     * @var string
     */
    protected $_sXPath = "//form[contains(@id, 'shipping')]";
}