<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once 'processorFactory.php';

$processorFactory = new \Magento\Framework\Error\ProcessorFactory();
$processor = $processorFactory->createProcessor();
$response = $processor->processNoCache();
$response->sendResponse();
