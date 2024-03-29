<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Chad Bender <support@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\FirstData\Block\Form;

/**
 * Credit card input form on checkout
 */
class Cc extends \ParadoxLabs\TokenBase\Block\Form\Cc
{
    /**
     * @var string
     */
    protected $brandingImage = 'ParadoxLabs_FirstData::images/logo.png';
}
