<?php

namespace AliorBank\Raty\Model\Adminhtml\Source;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Stdlib\DateTime;

class DatePicker extends Field
{
   public function render(AbstractElement $element)
   {
       $element->setDateFormat(DateTime::DATE_INTERNAL_FORMAT);
       $element->setShowsTime(false);
       return parent::render($element);
   }
}