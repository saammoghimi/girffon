SET @girffon_orders_user_id_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'orders'
    AND COLUMN_NAME = 'user_id'
);

SET @girffon_orders_user_id_sql = IF(
  @girffon_orders_user_id_exists = 0,
  'ALTER TABLE orders ADD user_id INT NULL AFTER id',
  'SELECT 1'
);

PREPARE girffon_orders_user_id_stmt FROM @girffon_orders_user_id_sql;
EXECUTE girffon_orders_user_id_stmt;
DEALLOCATE PREPARE girffon_orders_user_id_stmt;