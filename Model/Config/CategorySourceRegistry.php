<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Config;

class CategorySourceRegistry
{
    private $showAllCategories = false;

    public function getShowAllCategories(): bool
    {
        return $this->showAllCategories;
    }

    public function setShowAllCategories(bool $value): void
    {
        $this->showAllCategories = $value;
    }
}
