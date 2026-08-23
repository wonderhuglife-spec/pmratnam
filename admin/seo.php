<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin-lists.php';
pmr_admin_db_list('SEO', 'seo', 'seo_settings', ['id', 'site_name', 'updated_at'], 'No SEO settings row yet. The installer creates site-wide defaults.');
