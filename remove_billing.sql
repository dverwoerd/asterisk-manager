-- Verwijdert alle billing-gegevens. EERST EEN BACK-UP MAKEN (zie commando's in de README-sectie / chat):
--   mysqldump asterisk_manager invoice_items invoices customers rate_plan_rates rate_plans bel_tarieven > /root/billing_backup_$(date +%F).sql
-- Uitvoeren:  mysql asterisk_manager < remove_billing.sql
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS invoice_items;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS rate_plan_rates;
DROP TABLE IF EXISTS rate_plans;
DROP TABLE IF EXISTS bel_tarieven;
SET FOREIGN_KEY_CHECKS=1;

-- Oude billing-kolommen in cdr_records (de gespreksgeschiedenis zelf blijft behouden)
ALTER TABLE cdr_records
  DROP COLUMN rate_plan_id, DROP COLUMN rate_applied, DROP COLUMN cost,
  DROP COLUMN destination_name, DROP COLUMN invoiced, DROP COLUMN invoice_id;

DELETE FROM settings WHERE setting_key IN
 ('invoice_prefix','invoice_next_number','default_tax_rate','default_currency',
  'billing_tarief_nationaal','billing_tarief_mobiel','billing_starttarief');
