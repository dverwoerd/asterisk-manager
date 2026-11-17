-- Verwijdert de oude facturatie (facturen, tariefplannen, kosten in CDR).
-- BLIJFT BEHOUDEN: customers (klanten), bel_tarieven en billing_*-instellingen: die gebruikt de module Gespreksspecificaties.
-- EERST EEN BACK-UP MAKEN:
--   mysqldump asterisk_manager invoice_items invoices customers rate_plan_rates rate_plans > /root/billing_backup_$(date +%F).sql
-- Uitvoeren:  mysql asterisk_manager < remove_billing.sql
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS invoice_items;
DROP TABLE IF EXISTS invoices;
ALTER TABLE customers DROP FOREIGN KEY IF EXISTS customers_ibfk_1;
ALTER TABLE customers DROP COLUMN IF EXISTS rate_plan_id;
DROP TABLE IF EXISTS rate_plan_rates;
DROP TABLE IF EXISTS rate_plans;
SET FOREIGN_KEY_CHECKS=1;

-- Oude billing-kolommen in cdr_records (de gespreksgeschiedenis zelf blijft behouden)
ALTER TABLE cdr_records
  DROP COLUMN IF EXISTS rate_plan_id, DROP COLUMN IF EXISTS rate_applied, DROP COLUMN IF EXISTS cost,
  DROP COLUMN IF EXISTS destination_name, DROP COLUMN IF EXISTS invoiced, DROP COLUMN IF EXISTS invoice_id;

DELETE FROM settings WHERE setting_key IN ('invoice_prefix','invoice_next_number','default_currency');
