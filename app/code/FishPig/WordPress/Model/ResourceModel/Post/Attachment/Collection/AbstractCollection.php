<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

namespace FishPig\WordPress\Model\ResourceModel\Post\Attachment\Collection;

abstract class AbstractCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

	/**
	 * Load an attachment
	 *
	 * @param bool $printQuery
	 * @param bool $logQuery
	 * @return $this
	 */
    public function load($printQuery = false, $logQuery = false)
    {
		$this->getSelect()
			->where("post_type = ?", 'attachment')
			->where("post_mime_type LIKE 'image%'");
			
		return parent::load($printQuery, $logQuery);
    }
	
	/**
	 * Set the parent ID
	 *
	 * @param int $parentId = 0
	 * @return $this
	 */
	public function setParent($parentId = 0)
	{
		$this->getSelect()->where("post_parent = ?", $parentId);
		
		return $this;
	}
	
	/**
	 * After loading the collection, unserialize data
	 *
	 * @return $this
	 */
	protected function _afterLoad()
	{
		parent::_afterLoad();

		foreach($this->_items as $item) {
			$item->loadSerializedData();
		}
		
		return $this;
	}
}
