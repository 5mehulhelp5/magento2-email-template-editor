<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api\Data;

interface TemplateVersionInterface
{
    public const VERSION_ID = 'version_id';
    public const TEMPLATE_IDENTIFIER = 'template_identifier';
    public const VERSION_NUMBER = 'version_number';
    public const TEMPLATE_CONTENT = 'template_content';
    public const TEMPLATE_SUBJECT = 'template_subject';
    public const CUSTOM_CSS = 'custom_css';
    public const TAILWIND_CSS = 'tailwind_css';
    public const THEME_ID = 'theme_id';
    public const STORE_ID = 'store_id';
    public const VERSION_COMMENT = 'version_comment';
    public const ADMIN_USER_ID = 'admin_user_id';
    public const ADMIN_USERNAME = 'admin_username';
    public const PUBLISHED_AT = 'published_at';
    public const CREATED_AT = 'created_at';

    /**
     * Get version ID
     *
     * @return int|null
     */
    public function getVersionId(): ?int;

    /**
     * Set version ID
     *
     * @param int $versionId
     * @return $this
     */
    public function setVersionId(int $versionId): self;

    /**
     * Get template identifier
     *
     * @return string|null
     */
    public function getTemplateIdentifier(): ?string;

    /**
     * Set template identifier
     *
     * @param string $templateIdentifier
     * @return $this
     */
    public function setTemplateIdentifier(string $templateIdentifier): self;

    /**
     * Get version number
     *
     * @return int
     */
    public function getVersionNumber(): int;

    /**
     * Set version number
     *
     * @param int $versionNumber
     * @return $this
     */
    public function setVersionNumber(int $versionNumber): self;

    /**
     * Get template content
     *
     * @return string|null
     */
    public function getTemplateContent(): ?string;

    /**
     * Set template content
     *
     * @param string $templateContent
     * @return $this
     */
    public function setTemplateContent(string $templateContent): self;

    /**
     * Get template subject
     *
     * @return string|null
     */
    public function getTemplateSubject(): ?string;

    /**
     * Set template subject
     *
     * @param string|null $templateSubject
     * @return $this
     */
    public function setTemplateSubject(?string $templateSubject): self;

    /**
     * Get custom CSS
     *
     * @return string|null
     */
    public function getCustomCss(): ?string;

    /**
     * Set custom CSS
     *
     * @param string|null $customCss
     * @return $this
     */
    public function setCustomCss(?string $customCss): self;

    /**
     * Get Tailwind CSS
     *
     * @return string|null
     */
    public function getTailwindCss(): ?string;

    /**
     * Set Tailwind CSS
     *
     * @param string|null $tailwindCss
     * @return $this
     */
    public function setTailwindCss(?string $tailwindCss): self;

    /**
     * Get theme ID
     *
     * @return int|null
     */
    public function getThemeId(): ?int;

    /**
     * Set theme ID
     *
     * @param int|null $themeId
     * @return $this
     */
    public function setThemeId(?int $themeId): self;

    /**
     * Get store ID
     *
     * @return int
     */
    public function getStoreId(): int;

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId): self;

    /**
     * Get version comment
     *
     * @return string|null
     */
    public function getVersionComment(): ?string;

    /**
     * Set version comment
     *
     * @param string|null $versionComment
     * @return $this
     */
    public function setVersionComment(?string $versionComment): self;

    /**
     * Get admin user ID
     *
     * @return int|null
     */
    public function getAdminUserId(): ?int;

    /**
     * Set admin user ID
     *
     * @param int|null $adminUserId
     * @return $this
     */
    public function setAdminUserId(?int $adminUserId): self;

    /**
     * Get admin username
     *
     * @return string|null
     */
    public function getAdminUsername(): ?string;

    /**
     * Set admin username
     *
     * @param string|null $adminUsername
     * @return $this
     */
    public function setAdminUsername(?string $adminUsername): self;

    /**
     * Get published at timestamp
     *
     * @return string|null
     */
    public function getPublishedAt(): ?string;

    /**
     * Set published at timestamp
     *
     * @param string $publishedAt
     * @return $this
     */
    public function setPublishedAt(string $publishedAt): self;

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set creation time
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;
}
