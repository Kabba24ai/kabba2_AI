-- ─────────────────────────────────────────────────────────────────────────
-- READ-ONLY. Special-tax percentage defect — production exposure.
-- No credentials, no customer-identifying data.
-- ─────────────────────────────────────────────────────────────────────────

-- 1. The stored setting value, its column and type.
SELECT setting_name, setting_value, setting_type
FROM settings
WHERE setting_name IN ('special_taxes','sales_tax');

SELECT column_name, data_type, column_type
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'settings' AND column_name = 'setting_value';

-- 2. How many order lines carry nonzero frozen special tax, and the totals.
SELECT COUNT(*)                                                   AS lines_with_special_tax,
       ROUND(SUM(CAST(JSON_EXTRACT(product_data,'$.special_tax') AS DECIMAL(12,4))), 2) AS total_charged,
       MIN(CAST(JSON_EXTRACT(product_data,'$.special_tax') AS DECIMAL(12,4)))           AS smallest,
       MAX(CAST(JSON_EXTRACT(product_data,'$.special_tax') AS DECIMAL(12,4)))           AS largest
FROM order_products
WHERE deleted_at IS NULL
  AND JSON_EXTRACT(product_data,'$.special_tax') IS NOT NULL
  AND CAST(JSON_EXTRACT(product_data,'$.special_tax') AS DECIMAL(12,4)) > 0;

-- 3. THE DIAGNOSTIC. Implied rate per line = special_tax / sub_total.
--    ~0.0002 proves the 100x under-charge; ~0.02 would mean correct.
SELECT ROUND(CAST(JSON_EXTRACT(product_data,'$.special_tax') AS DECIMAL(12,6)) / sub_total, 6) AS implied_rate,
       COUNT(*) AS lines,
       MIN(DATE(created_at)) AS first_seen,
       MAX(DATE(created_at)) AS last_seen
FROM order_products
WHERE deleted_at IS NULL AND sub_total > 0
  AND CAST(JSON_EXTRACT(product_data,'$.special_tax') AS DECIMAL(12,6)) > 0
GROUP BY implied_rate
ORDER BY lines DESC;

-- 4. Has the rate ever differed? Monthly, to show whether this is
--    long-standing or recently introduced.
SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
       COUNT(*) AS lines,
       ROUND(AVG(CAST(JSON_EXTRACT(product_data,'$.special_tax') AS DECIMAL(12,6)) / NULLIF(sub_total,0)), 6) AS avg_implied_rate
FROM order_products
WHERE deleted_at IS NULL AND sub_total > 0
  AND CAST(JSON_EXTRACT(product_data,'$.special_tax') AS DECIMAL(12,6)) > 0
GROUP BY month ORDER BY month;

-- 5. Release 1 columns agree with the frozen snapshot. EXPECT 0 ROWS.
SELECT id, order_id, special_tax,
       CAST(JSON_EXTRACT(product_data,'$.special_tax') AS DECIMAL(12,2)) AS frozen
FROM order_products
WHERE deleted_at IS NULL
  AND ABS(special_tax - COALESCE(CAST(JSON_EXTRACT(product_data,'$.special_tax') AS DECIMAL(12,2)),0)) > 0.001;
