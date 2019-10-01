<?php

namespace Auctane\Api\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class GenerateApiKeyButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Auctane_Api::system/config/generate.phtml';

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return ajax url for synchronize button
     *
     * @return string
     */
    public function getApiKeyGenerationUrl()
    {
        return $this->getUrl('auctane_api/apikey');
    }

    /**
     * Generate synchronize button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'class' => 'primary',
                'id' => 'generate_and_save_api_key',
                'label' => __('Generate and save api key'),
            ]
        );

        return $button->toHtml();
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
