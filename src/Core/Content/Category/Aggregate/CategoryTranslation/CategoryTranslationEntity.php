<?php declare(strict_types=1);

namespace Shopware\Core\Content\Category\Aggregate\CategoryTranslation;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Shopware\Core\System\Language\LanguageEntity;

class CategoryTranslationEntity extends TranslationEntity
{
    /**
     * @var string
     */
    protected $categoryId;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var array|null
     */
    protected $breadcrumb;

    /**
     * @var CategoryEntity|null
     */
    protected $category;

    /**
     * @var LanguageEntity|null
     */
    protected $language;

    /**
     * @var array|null
     */
    protected $customFields;

    /**
     * @var array|null
     */
    protected $slotConfig;

    /**
     * @internal (flag:FEATURE_NEXT_13504)
     *
     * @var string|null
     */
    protected $linkType;

    /**
     * @internal (flag:FEATURE_NEXT_13504)
     *
     * @var bool|null
     */
    protected $linkNewTab;

    /**
     * @internal (flag:FEATURE_NEXT_13504)
     *
     * @var string|null
     */
    protected $internalLink;

    /**
     * @var string|null
     */
    protected $externalLink;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $metaTitle;

    /**
     * @var string|null
     */
    protected $metaDescription;

    /**
     * @var string|null
     */
    protected $keywords;

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function setCategoryId(string $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getCategory(): ?CategoryEntity
    {
        return $this->category;
    }

    public function setCategory(CategoryEntity $category): void
    {
        $this->category = $category;
    }

    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    public function setCustomFields(?array $customFields): void
    {
        $this->customFields = $customFields;
    }

    public function getSlotConfig(): ?array
    {
        return $this->slotConfig;
    }

    public function setSlotConfig(array $slotConfig): void
    {
        $this->slotConfig = $slotConfig;
    }

    /**
     * @internal (flag:FEATURE_NEXT_13504)
     */
    public function getLinkType(): ?string
    {
        return $this->linkType;
    }

    /**
     * @internal (flag:FEATURE_NEXT_13504)
     */
    public function setLinkType(?string $linkType): void
    {
        $this->linkType = $linkType;
    }

    /**
     * @internal (flag:FEATURE_NEXT_13504)
     */
    public function getLinkNewTab(): ?bool
    {
        return $this->linkNewTab;
    }

    /**
     * @internal (flag:FEATURE_NEXT_13504)
     */
    public function setLinkNewTab(?bool $linkNewTab): void
    {
        $this->linkNewTab = $linkNewTab;
    }

    /**
     * @internal (flag:FEATURE_NEXT_13504)
     */
    public function getInternalLink(): ?string
    {
        return $this->internalLink;
    }

    /**
     * @internal (flag:FEATURE_NEXT_13504)
     */
    public function setInternalLink(?string $internalLink): void
    {
        $this->internalLink = $internalLink;
    }

    public function getExternalLink(): ?string
    {
        return $this->externalLink;
    }

    public function setExternalLink(string $externalLink): void
    {
        $this->externalLink = $externalLink;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getBreadcrumb(): ?array
    {
        return $this->breadcrumb;
    }

    public function setBreadcrumb(?array $breadcrumb): void
    {
        $this->breadcrumb = $breadcrumb;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): void
    {
        $this->metaTitle = $metaTitle;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): void
    {
        $this->metaDescription = $metaDescription;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): void
    {
        $this->keywords = $keywords;
    }
}
