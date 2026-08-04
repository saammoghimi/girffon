CREATE TABLE IF NOT EXISTS gift_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gift_code VARCHAR(32) NOT NULL,
    initial_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    remaining_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    buyer_name VARCHAR(190) NULL,
    buyer_email VARCHAR(190) NULL,
    recipient_name VARCHAR(190) NULL,
    recipient_email VARCHAR(190) NULL,
    gift_message TEXT NULL,
    delivery_type VARCHAR(20) NOT NULL DEFAULT 'digital',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    order_id INT NULL,
    source_line_key CHAR(40) NULL,
    qr_payload TEXT NULL,
    barcode_value VARCHAR(64) NULL,
    public_reference CHAR(32) NOT NULL,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_gift_cards_gift_code (gift_code),
    UNIQUE KEY uq_gift_cards_public_reference (public_reference),
    UNIQUE KEY uq_gift_cards_order_line (order_id, source_line_key),
    KEY idx_gift_cards_status (status),
    KEY idx_gift_cards_order_id (order_id),
    KEY idx_gift_cards_recipient_email (recipient_email),
    KEY idx_gift_cards_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gift_card_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gift_card_id INT NOT NULL,
    order_id INT NULL,
    transaction_type VARCHAR(30) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    balance_before DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    balance_after DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    request_fingerprint CHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_gift_card_transactions_card_id (gift_card_id),
    KEY idx_gift_card_transactions_order_id (order_id),
    KEY idx_gift_card_transactions_type (transaction_type),
    UNIQUE KEY uq_gift_card_transactions_fingerprint (request_fingerprint),
    UNIQUE KEY uq_gift_card_transactions_order_type (gift_card_id, order_id, transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER $$

DROP PROCEDURE IF EXISTS girffon_apply_gift_card_columns $$

CREATE PROCEDURE girffon_apply_gift_card_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'gift_card_code'
    ) THEN
        ALTER TABLE orders ADD COLUMN gift_card_code VARCHAR(32) NULL AFTER tracking_code;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'gift_card_amount'
    ) THEN
        ALTER TABLE orders ADD COLUMN gift_card_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER gift_card_code;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'amount_due'
    ) THEN
        ALTER TABLE orders ADD COLUMN amount_due DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER gift_card_amount;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'gift_card_amount'
    ) THEN
        ALTER TABLE invoices ADD COLUMN gift_card_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER shipping;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'item_type'
    ) THEN
        ALTER TABLE order_items ADD COLUMN item_type VARCHAR(40) NOT NULL DEFAULT 'product' AFTER product_id;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'metadata_json'
    ) THEN
        ALTER TABLE order_items ADD COLUMN metadata_json LONGTEXT NULL AFTER image;
    END IF;
END $$

CALL girffon_apply_gift_card_columns() $$

DROP PROCEDURE IF EXISTS girffon_apply_gift_card_columns $$

DELIMITER ;