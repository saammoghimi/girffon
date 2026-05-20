CREATE TABLE IF NOT EXISTS user_preferences (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    promotional_emails TINYINT(1) NOT NULL DEFAULT 1,
    catalog_emails TINYINT(1) NOT NULL DEFAULT 1,
    birthday_discount_emails TINYINT(1) NOT NULL DEFAULT 1,
    order_updates TINYINT(1) NOT NULL DEFAULT 1,
    sms_notifications TINYINT(1) NOT NULL DEFAULT 0,
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_user_preferences_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    email VARCHAR(190) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'subscribed',
    source VARCHAR(60) NOT NULL DEFAULT 'profile',
    accepts_promotional_emails TINYINT(1) NOT NULL DEFAULT 0,
    accepts_catalog_emails TINYINT(1) NOT NULL DEFAULT 1,
    subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_newsletter_email (email),
    KEY idx_newsletter_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS newsletter_campaign_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    campaign_id VARCHAR(64) NOT NULL,
    user_id INT UNSIGNED NULL,
    recipient_name VARCHAR(150) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(190) NOT NULL,
    message MEDIUMTEXT NOT NULL,
    attachment_url VARCHAR(255) NOT NULL DEFAULT '',
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    transport VARCHAR(40) NOT NULL DEFAULT '',
    error_message TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_newsletter_campaign (campaign_id),
    KEY idx_newsletter_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
