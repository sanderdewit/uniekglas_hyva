<?php
declare(strict_types=1);

namespace Sander\RemoveCoupon\Plugin;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

class LayoutProcessorPlugin
{
    public function afterProcess(LayoutProcessor $subject, array $jsLayout): array
    {
        $parentPath = [
            'components','checkout','children','steps','children','billing-step',
            'children','payment','children','afterMethods','children'
        ];

        $parent =& $jsLayout;
        foreach ($parentPath as $key) {
            if (!isset($parent[$key])) {
                return $jsLayout; // structure differs, do nothing
            }
            $parent =& $parent[$key];
        }

        if (isset($parent['discount'])) {
            unset($parent['discount']); // remove the coupon UI component
        }

        return $jsLayout;
    }
}
