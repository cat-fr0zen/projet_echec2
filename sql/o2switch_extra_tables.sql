CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    subscriber_id VARCHAR(64) PRIMARY KEY,
    email VARCHAR(254) NOT NULL,
    email_normalized VARCHAR(254) NOT NULL UNIQUE,
    unsubscribe_token VARCHAR(80) NOT NULL UNIQUE,
    active TINYINT(1) NOT NULL DEFAULT 1,
    consent_version VARCHAR(40) NOT NULL DEFAULT 'newsletter-2026-05',
    source VARCHAR(80) NOT NULL DEFAULT 'site',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_campaigns (
    campaign_id VARCHAR(64) PRIMARY KEY,
    event_type VARCHAR(30) NOT NULL,
    subject VARCHAR(220) NOT NULL,
    title VARCHAR(220) NOT NULL,
    event_url VARCHAR(500) NULL,
    message_text TEXT NOT NULL,
    sender_email VARCHAR(254) NOT NULL,
    sender_name VARCHAR(190) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'queued',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX ix_newsletter_campaigns_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_queue (
    queue_id VARCHAR(64) PRIMARY KEY,
    campaign_id VARCHAR(64) NOT NULL,
    newsletter_abonnement_id VARCHAR(50) NULL,
    recipient_email VARCHAR(254) NOT NULL,
    unsubscribe_token VARCHAR(80) NULL,
    template_type VARCHAR(30) NOT NULL DEFAULT 'actualite',
    event_type VARCHAR(30) NOT NULL DEFAULT 'newsletter',
    subject VARCHAR(220) NOT NULL,
    title VARCHAR(220) NOT NULL,
    message_text TEXT NOT NULL,
    event_url VARCHAR(500) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    attempt_count INT NOT NULL DEFAULT 0,
    last_error VARCHAR(1000) NULL,
    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_at DATETIME NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX ix_newsletter_queue_status (status, available_at),
    CONSTRAINT fk_newsletter_queue_campaign FOREIGN KEY (campaign_id) REFERENCES newsletter_campaigns (campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents (
    document_id VARCHAR(64) PRIMARY KEY,
    title VARCHAR(220) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    relative_path VARCHAR(255) NOT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    author_identifier VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX ix_documents_public (is_public, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_cache (
    cache_key VARCHAR(190) PRIMARY KEY,
    service_name VARCHAR(60) NOT NULL,
    payload_json LONGTEXT NOT NULL,
    http_status INT NOT NULL DEFAULT 200,
    metadata_json LONGTEXT NULL,
    expires_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX ix_api_cache_service_expire (service_name, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
