-- ============================================================
--  MonkeyZone POS System — PostgreSQL Database Schema
--  Student: Nilesh Vedpathak | BCA Final Year Project
-- ============================================================

-- Drop existing tables (order matters due to foreign keys)
DROP TABLE IF EXISTS transaction_items  CASCADE;
DROP TABLE IF EXISTS transactions       CASCADE;
DROP TABLE IF EXISTS party_bookings     CASCADE;
DROP TABLE IF EXISTS customers          CASCADE;
DROP TABLE IF EXISTS users              CASCADE;

-- ============================================================
--  USERS  (staff + admin)
-- ============================================================
CREATE TABLE users (
    id            SERIAL PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(20)  NOT NULL DEFAULT 'staff',  -- 'admin' | 'staff'
    created_at    TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- ============================================================
--  CUSTOMERS
-- ============================================================
CREATE TABLE customers (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    phone      VARCHAR(15)  NOT NULL UNIQUE,
    created_at TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- ============================================================
--  TRANSACTIONS  (point-of-sale sales)
-- ============================================================
CREATE TABLE transactions (
    id             SERIAL PRIMARY KEY,
    transaction_id VARCHAR(40)    NOT NULL UNIQUE,  -- TXN-YYYYMMDD-HHMMSS-XXXX
    customer_id    INTEGER        REFERENCES customers(id),
    payment_method VARCHAR(20)    NOT NULL,          -- Cash | Card | UPI
    subtotal       NUMERIC(10,2)  NOT NULL DEFAULT 0,
    discount_type  VARCHAR(10)    DEFAULT '',        -- flat | percent | ''
    discount_value NUMERIC(10,2)  DEFAULT 0,
    discount_amount NUMERIC(10,2) DEFAULT 0,
    total          NUMERIC(10,2)  NOT NULL,
    created_by     INTEGER        REFERENCES users(id),
    created_at     TIMESTAMP      NOT NULL DEFAULT NOW()
);

-- ============================================================
--  TRANSACTION ITEMS  (line items per sale)
-- ============================================================
CREATE TABLE transaction_items (
    id             SERIAL PRIMARY KEY,
    transaction_id INTEGER        NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
    item_name      VARCHAR(100)   NOT NULL,
    qty            INTEGER        NOT NULL DEFAULT 1,
    unit_price     NUMERIC(10,2)  NOT NULL,
    tax_percent    NUMERIC(5,2)   NOT NULL DEFAULT 18,
    item_total     NUMERIC(10,2)  NOT NULL,
    created_at     TIMESTAMP      NOT NULL DEFAULT NOW()
);

-- ============================================================
--  PARTY BOOKINGS
-- ============================================================
CREATE TABLE party_bookings (
    id              SERIAL PRIMARY KEY,
    booking_id      VARCHAR(40)   NOT NULL UNIQUE,  -- PARTY-YYYYMMDD-XXXXX
    customer_id     INTEGER       REFERENCES customers(id),
    event_date      DATE          NOT NULL,
    activity        VARCHAR(100)  NOT NULL,
    event_type      VARCHAR(50)   NOT NULL,
    expected_guests INTEGER       DEFAULT 0,
    advance_amount  NUMERIC(10,2) NOT NULL DEFAULT 0,
    payment_method  VARCHAR(20)   NOT NULL,
    created_by      INTEGER       REFERENCES users(id),
    created_at      TIMESTAMP     NOT NULL DEFAULT NOW()
);

-- ============================================================
--  INDEXES  (for fast lookups)
-- ============================================================
CREATE INDEX idx_customers_phone      ON customers(phone);
CREATE INDEX idx_customers_name       ON customers(name);
CREATE INDEX idx_transactions_date    ON transactions(created_at);
CREATE INDEX idx_transactions_cust    ON transactions(customer_id);
CREATE INDEX idx_tx_items_tx_id       ON transaction_items(transaction_id);
CREATE INDEX idx_party_event_date     ON party_bookings(event_date);
CREATE INDEX idx_party_customer       ON party_bookings(customer_id);

-- ============================================================
--  SEED DATA — Default Users
--  Admin password: admin123
--  Staff password: staff123
-- ============================================================
INSERT INTO users (username, password_hash, role) VALUES
    ('admin',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
    ('staff1', '$2y$10$TKh8H1.PdfskRVyB6rBQDOQJGNVXKOXxX5vxRQW5TuUKsRAJ0HgIe', 'staff');

-- Note: The hashes above are bcrypt of 'password' (Laravel default hash for testing).
-- After first login, the system will auto-upgrade to a newly generated hash.
-- For production, generate proper hashes using PHP: password_hash('yourpassword', PASSWORD_BCRYPT)

-- ============================================================
--  SAMPLE DATA (optional — remove in production)
-- ============================================================
INSERT INTO customers (name, phone) VALUES
    ('Rahul Sharma',   '9876543210'),
    ('Priya Patel',    '9123456789'),
    ('Amit Kulkarni',  '9988776655');

-- ============================================================
--  USEFUL VIEWS
-- ============================================================

-- Daily revenue summary
CREATE OR REPLACE VIEW v_daily_revenue AS
SELECT
    DATE(created_at)      AS sale_date,
    COUNT(*)              AS tx_count,
    SUM(total)            AS revenue,
    AVG(total)            AS avg_order
FROM transactions
GROUP BY DATE(created_at)
ORDER BY sale_date DESC;

-- Customer summary
CREATE OR REPLACE VIEW v_customer_summary AS
SELECT
    c.id,
    c.name,
    c.phone,
    COUNT(DISTINCT t.id)  AS visit_count,
    COALESCE(SUM(t.total), 0) AS total_spent,
    MAX(t.created_at)     AS last_visit
FROM customers c
LEFT JOIN transactions t ON c.id = t.customer_id
GROUP BY c.id, c.name, c.phone;

-- ============================================================
--  SETUP INSTRUCTIONS
-- ============================================================
-- 1. Create database:  createdb pos_db
-- 2. Run this file:    psql -U postgres -d pos_db -f schema.sql
-- 3. Update db.php with your actual password
-- 4. Default login:    username=admin, password=password
-- 5. Change password immediately after first login!
-- ============================================================
