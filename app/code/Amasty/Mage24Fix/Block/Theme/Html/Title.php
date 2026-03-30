<?php

declare(strict_types=1);

namespace Amasty\Mage24Fix\Block\Theme\Html;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Title
 * 
 * Fix fatal with plugin to title block only for 2.4.0 version - https://github.com/magento/magento2/issues/28981
 */
class Title extends Template
{
    /**
     * Config path to 'Translate Title' header settings
     */
    const XML_PATH_HEADER_TRANSLATE_TITLE = 'design/header/translate_title';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Own page title to display on the page
     *
     * @var string
     */
    protected $pageTitle;

    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Provide own page title or pick it from Head Block
     *
     * @return string
     */
    public function getPageTitle()
    {
        if (!empty($this->pageTitle)) {
            return $this->pageTitle;
        }

        $pageTitle = $this->pageConfig->getTitle()->getShort();

        return $this->shouldTranslateTitle() ? __($pageTitle) : $pageTitle;
    }

    /**
     * Provide own page content heading
     *
     * @return string
     */
    public function getPageHeading()
    {
        $pageTitle = !empty($this->pageTitle) ? $this->pageTitle : $this->pageConfig->getTitle()->getShortHeading();

        return $this->shouldTranslateTitle() ? __($pageTitle) : $pageTitle;
    }

    /**
     * Set own page title
     *
     * @param string $pageTitle
     * @return void
     */
    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;
    }

    /**
     * Check if page title should be translated
     *
     * @return bool
     */
    private function shouldTranslateTitle(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_HEADER_TRANSLATE_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }
}
