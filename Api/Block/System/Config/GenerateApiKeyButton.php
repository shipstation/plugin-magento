<?php

namespace Auctane\Api\Block\System\Config;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class GenerateApiKeyButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Auctane_Api::system/config/generate.phtml';

    /**
     * Return ajax url for synchronize button
     *
     * @return string
     */
    public function getApiKeyGenerationUrl(): string
    {
        return $this->getUrl('auctane_api/apikey');
    }

    /**
     * Generate synchronize button html
     *
     * @return string
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getButtonHtml(): string
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $button = $this->getLayout()->createBlock(
            Button::class
        )->setData(
            [
                'class' => 'primary',
                'id' => 'generate_and_save_api_key',
                'label' => __('Generate and save api key'),
            ]
        );

        return $button->toHtml();
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }
}
