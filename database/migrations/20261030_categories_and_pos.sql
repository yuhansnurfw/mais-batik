USE mais_batik;

CREATE TABLE categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(60) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB;

INSERT INTO categories (name, slug)
VALUES
    ('Batik pria', 'pria'),
    ('Batik anak', 'anak'),
    ('Batik wanita', 'wanita');

ALTER TABLE products
    ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER id;

UPDATE products AS p
JOIN categories AS c ON c.slug = p.category
SET p.category_id = c.id;

ALTER TABLE products
    DROP INDEX idx_products_category_status,
    DROP COLUMN category,
    MODIFY COLUMN category_id BIGINT UNSIGNED NOT NULL,
    ADD INDEX idx_products_category_status (category_id, status),
    ADD CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;

CREATE TABLE sales_transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    total_amount BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_sales_transactions_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE sales_transaction_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    transaction_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    product_name VARCHAR(120) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price BIGINT UNSIGNED NOT NULL,
    subtotal BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_sales_items_transaction (transaction_id),
    INDEX idx_sales_items_product (product_id),
    CONSTRAINT fk_sales_items_transaction
        FOREIGN KEY (transaction_id) REFERENCES sales_transactions (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sales_items_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;
