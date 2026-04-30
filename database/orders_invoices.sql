CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  order_number VARCHAR(50) NOT NULL UNIQUE,
  customer_name VARCHAR(150) NOT NULL,
  customer_email VARCHAR(150) NULL,
  phone VARCHAR(50) NULL,
  address VARCHAR(255) NULL,
  city VARCHAR(120) NULL,
  country VARCHAR(120) NULL,
  postcode VARCHAR(40) NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  shipping DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_method VARCHAR(50) NOT NULL DEFAULT 'bank_transfer',
  payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  order_status ENUM('new', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'new',
  tracking_code VARCHAR(100) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id VARCHAR(100) NULL,
  name VARCHAR(200) NULL,
  product_name VARCHAR(200) NOT NULL,
  sku VARCHAR(100) NULL,
  size VARCHAR(50) NULL,
  color VARCHAR(50) NULL,
  quantity INT NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  image VARCHAR(255) NULL,
  CONSTRAINT fk_order_items_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  user_id INT NULL,
  invoice_number VARCHAR(50) NOT NULL UNIQUE,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  tax DECIMAL(10,2) NOT NULL DEFAULT 0,
  shipping DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  invoice_status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
  invoice_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  pdf_path VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_invoices_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO orders (order_number, customer_name, customer_email, total, payment_status, order_status, tracking_code, created_at)
SELECT 'GF-2026-1001', 'Marco Bellini', 'marco.bellini@example.com', 149.80, 'paid', 'processing', 'IT-TRACK-1001', '2026-04-24 10:15:00'
WHERE NOT EXISTS (
  SELECT 1 FROM orders WHERE order_number = 'GF-2026-1001'
);

INSERT INTO orders (order_number, customer_name, customer_email, total, payment_status, order_status, tracking_code, created_at)
SELECT 'GF-2026-1002', 'Elena Rossi', 'elena.rossi@example.com', 89.50, 'paid', 'shipped', 'IT-TRACK-1002', '2026-04-25 09:30:00'
WHERE NOT EXISTS (
  SELECT 1 FROM orders WHERE order_number = 'GF-2026-1002'
);

INSERT INTO order_items (order_id, product_name, sku, size, color, quantity, price, image)
SELECT orders.id, 'Organic T-Shirt Black', 'GF-BLK-101', 'L', 'Black', 2, 39.90, NULL
FROM orders
WHERE orders.order_number = 'GF-2026-1001'
  AND NOT EXISTS (
    SELECT 1 FROM order_items WHERE order_id = orders.id AND sku = 'GF-BLK-101'
  );

INSERT INTO order_items (order_id, product_name, sku, size, color, quantity, price, image)
SELECT orders.id, 'Premium Gold Hoodie', 'GF-HOOD-201', 'M', 'Gold', 1, 69.00, NULL
FROM orders
WHERE orders.order_number = 'GF-2026-1001'
  AND NOT EXISTS (
    SELECT 1 FROM order_items WHERE order_id = orders.id AND sku = 'GF-HOOD-201'
  );

INSERT INTO order_items (order_id, product_name, sku, size, color, quantity, price, image)
SELECT orders.id, 'Limited Edition Sweatshirt', 'GF-SWT-220', 'M', 'Sand', 1, 89.50, NULL
FROM orders
WHERE orders.order_number = 'GF-2026-1002'
  AND NOT EXISTS (
    SELECT 1 FROM order_items WHERE order_id = orders.id AND sku = 'GF-SWT-220'
  );

INSERT INTO invoices (order_id, invoice_number, invoice_status, invoice_total, pdf_path, created_at)
SELECT orders.id, 'INV-2026-1001', 'paid', 149.80, NULL, '2026-04-24 10:45:00'
FROM orders
WHERE orders.order_number = 'GF-2026-1001'
  AND NOT EXISTS (
    SELECT 1 FROM invoices WHERE invoice_number = 'INV-2026-1001'
  );

INSERT INTO invoices (order_id, invoice_number, invoice_status, invoice_total, pdf_path, created_at)
SELECT orders.id, 'INV-2026-1002', 'paid', 89.50, NULL, '2026-04-25 10:00:00'
FROM orders
WHERE orders.order_number = 'GF-2026-1002'
  AND NOT EXISTS (
    SELECT 1 FROM invoices WHERE invoice_number = 'INV-2026-1002'
  );
