<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Exception\CouldNotRefundExceptionInterface;

/**
 * Class CouldNotRefundException
 */
class CouldNotRefundException extends LocalizedException implements CouldNotRefundExceptionInterface
{
}
