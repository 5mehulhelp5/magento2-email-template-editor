<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateVersionInterface;
use Hryvinskyi\EmailTemplateEditor\Model\ResourceModel\TemplateVersion as TemplateVersionResource;
use Magento\Framework\Model\AbstractModel;

class TemplateVersion extends AbstractModel implements TemplateVersionInterface
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(TemplateVersionResource::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getVersionId(): ?int
    {
        $id = $this->getData(self::VERSION_ID);

        return $id !== null ? (int)$id : null;
    }

    /**
     * {@inheritDoc}
     */
    public function setVersionId(int $versionId): self
    {
        return $this->setData(self::VERSION_ID, $versionId);
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplateIdentifier(): ?string
    {
        return $this->getData(self::TEMPLATE_IDENTIFIER);
    }

    /**
     * {@inheritDoc}
     */
    public function setTemplateIdentifier(string $templateIdentifier): self
    {
        return $this->setData(self::TEMPLATE_IDENTIFIER, $templateIdentifier);
    }

    /**
     * {@inheritDoc}
     */
    public function getVersionNumber(): int
    {
        return (int)$this->getData(self::VERSION_NUMBER);
    }

    /**
     * {@inheritDoc}
     */
    public function setVersionNumber(int $versionNumber): self
    {
        return $this->setData(self::VERSION_NUMBER, $versionNumber);
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplateContent(): ?string
    {
        return $this->getData(self::TEMPLATE_CONTENT);
    }

    /**
     * {@inheritDoc}
     */
    public function setTemplateContent(string $templateContent): self
    {
        return $this->setData(self::TEMPLATE_CONTENT, $templateContent);
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplateSubject(): ?string
    {
        return $this->getData(self::TEMPLATE_SUBJECT);
    }

    /**
     * {@inheritDoc}
     */
    public function setTemplateSubject(?string $templateSubject): self
    {
        return $this->setData(self::TEMPLATE_SUBJECT, $templateSubject);
    }

    /**
     * {@inheritDoc}
     */
    public function getCustomCss(): ?string
    {
        return $this->getData(self::CUSTOM_CSS);
    }

    /**
     * {@inheritDoc}
     */
    public function setCustomCss(?string $customCss): self
    {
        return $this->setData(self::CUSTOM_CSS, $customCss);
    }

    /**
     * {@inheritDoc}
     */
    public function getTailwindCss(): ?string
    {
        return $this->getData(self::TAILWIND_CSS);
    }

    /**
     * {@inheritDoc}
     */
    public function setTailwindCss(?string $tailwindCss): self
    {
        return $this->setData(self::TAILWIND_CSS, $tailwindCss);
    }

    /**
     * {@inheritDoc}
     */
    public function getThemeId(): ?int
    {
        $id = $this->getData(self::THEME_ID);

        return $id !== null ? (int)$id : null;
    }

    /**
     * {@inheritDoc}
     */
    public function setThemeId(?int $themeId): self
    {
        return $this->setData(self::THEME_ID, $themeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    /**
     * {@inheritDoc}
     */
    public function setStoreId(int $storeId): self
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getVersionComment(): ?string
    {
        return $this->getData(self::VERSION_COMMENT);
    }

    /**
     * {@inheritDoc}
     */
    public function setVersionComment(?string $versionComment): self
    {
        return $this->setData(self::VERSION_COMMENT, $versionComment);
    }

    /**
     * {@inheritDoc}
     */
    public function getAdminUserId(): ?int
    {
        $id = $this->getData(self::ADMIN_USER_ID);

        return $id !== null ? (int)$id : null;
    }

    /**
     * {@inheritDoc}
     */
    public function setAdminUserId(?int $adminUserId): self
    {
        return $this->setData(self::ADMIN_USER_ID, $adminUserId);
    }

    /**
     * {@inheritDoc}
     */
    public function getAdminUsername(): ?string
    {
        return $this->getData(self::ADMIN_USERNAME);
    }

    /**
     * {@inheritDoc}
     */
    public function setAdminUsername(?string $adminUsername): self
    {
        return $this->setData(self::ADMIN_USERNAME, $adminUsername);
    }

    /**
     * {@inheritDoc}
     */
    public function getPublishedAt(): ?string
    {
        return $this->getData(self::PUBLISHED_AT);
    }

    /**
     * {@inheritDoc}
     */
    public function setPublishedAt(string $publishedAt): self
    {
        return $this->setData(self::PUBLISHED_AT, $publishedAt);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * {@inheritDoc}
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
