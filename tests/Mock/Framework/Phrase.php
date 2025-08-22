<?php

namespace Magento\Framework;

/**
 * Mock implementation of Magento's Phrase class for testing
 * This allows tests to run without requiring the full Magento framework
 */
class Phrase
{
    /**
     * @var string
     */
    private string $text;

    /**
     * @var array
     */
    private array $arguments;

    /**
     * Constructor
     *
     * @param string $text
     * @param array $arguments
     */
    public function __construct(string $text, array $arguments = [])
    {
        $this->text = $text;
        $this->arguments = $arguments;
    }

    /**
     * Render the phrase
     *
     * @return string
     */
    public function render(): string
    {
        if (empty($this->arguments)) {
            return $this->text;
        }

        return vsprintf($this->text, $this->arguments);
    }

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}