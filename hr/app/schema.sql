CREATE TABLE [dbo].[ep_users] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 username NVARCHAR(80) NOT NULL UNIQUE,
 password_hash NVARCHAR(255) NOT NULL,
 name NVARCHAR(150) NOT NULL,
 role NVARCHAR(20) NOT NULL DEFAULT N'employee',
 manager_id BIGINT NULL,
 department NVARCHAR(100) NOT NULL DEFAULT N'',
 job_title NVARCHAR(100) NOT NULL DEFAULT N'',
 staff_type NVARCHAR(20) NOT NULL DEFAULT N'office',
 active TINYINT NOT NULL DEFAULT 1,
 show_sick TINYINT NOT NULL DEFAULT 0,
 hired_on DATE NULL,
 salary DECIMAL(12,3) NOT NULL DEFAULT 0,
 profile_json NVARCHAR(MAX) NOT NULL,
 created_at DATETIME2(0) NOT NULL
);

CREATE INDEX [ep_manager] ON [dbo].[ep_users] (manager_id);

CREATE TABLE [dbo].[ep_settings] (
 setting_key NVARCHAR(100) PRIMARY KEY,
 value_json NVARCHAR(MAX) NOT NULL
);

CREATE TABLE [dbo].[ep_policies] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 user_id BIGINT NULL,
 effective_from DATE NOT NULL,
 effective_to DATE NULL,
 label NVARCHAR(150) NOT NULL,
 value_json NVARCHAR(MAX) NOT NULL,
 created_at DATETIME2(0) NOT NULL
);

CREATE INDEX [ep_policy_date] ON [dbo].[ep_policies] (user_id,effective_from);

CREATE TABLE [dbo].[ep_events] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 request_id NCHAR(36) NOT NULL UNIQUE,
 user_id BIGINT NOT NULL,
 event_type NVARCHAR(30) NOT NULL,
 received_at DATETIME2(0) NOT NULL,
 work_date DATE NOT NULL,
 latitude DECIMAL(10,7) NOT NULL,
 longitude DECIMAL(10,7) NOT NULL,
 accuracy DECIMAL(10,2) NOT NULL,
 photo_id BIGINT NULL,
 evidence_id BIGINT NULL,
 policy_id BIGINT NULL,
 external_permission_id BIGINT NULL
);

CREATE INDEX [ep_event_user_day] ON [dbo].[ep_events] (user_id,work_date,received_at);

CREATE TABLE [dbo].[ep_files] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 user_id BIGINT NOT NULL,
 disk_name NVARCHAR(100) NOT NULL,
 original_name NVARCHAR(180) NOT NULL,
 mime NVARCHAR(80) NOT NULL,
 size_bytes BIGINT NOT NULL,
 sha256 NCHAR(64) NOT NULL,
 created_at DATETIME2(0) NOT NULL
);

CREATE TABLE [dbo].[ep_records] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 module NVARCHAR(30) NOT NULL,
 user_id BIGINT NOT NULL,
 title NVARCHAR(180) NOT NULL,
 date_from DATE NULL,
 date_to DATE NULL,
 amount DECIMAL(12,3) NOT NULL DEFAULT 0,
 status NVARCHAR(30) NOT NULL DEFAULT N'pending',
 body_json NVARCHAR(MAX) NOT NULL,
 file_id BIGINT NULL,
 reviewed_by BIGINT NULL,
 review_note NVARCHAR(MAX) NULL,
 created_at DATETIME2(0) NOT NULL,
 updated_at DATETIME2(0) NOT NULL
);

CREATE INDEX [ep_record_module] ON [dbo].[ep_records] (module,user_id,status,date_from);

CREATE TABLE [dbo].[ep_notifications] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 user_id BIGINT NOT NULL,
 title NVARCHAR(180) NOT NULL,
 body NVARCHAR(MAX) NOT NULL,
 require_ack TINYINT NOT NULL DEFAULT 0,
 created_at DATETIME2(0) NOT NULL,
 read_at DATETIME2(0) NULL,
 acknowledged_at DATETIME2(0) NULL
);

CREATE INDEX [ep_notice_user] ON [dbo].[ep_notifications] (user_id,id);

CREATE TABLE [dbo].[ep_locations] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 user_id BIGINT NOT NULL,
 received_at DATETIME2(0) NOT NULL,
 latitude DECIMAL(10,7) NOT NULL,
 longitude DECIMAL(10,7) NOT NULL,
 accuracy DECIMAL(10,2) NOT NULL,
 proof_id BIGINT NULL
);

CREATE INDEX [ep_location_latest] ON [dbo].[ep_locations] (user_id,id);

CREATE TABLE [dbo].[ep_audit] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 user_id BIGINT NULL,
 action NVARCHAR(80) NOT NULL,
 subject NVARCHAR(120) NOT NULL,
 detail_json NVARCHAR(MAX) NOT NULL,
 created_at DATETIME2(0) NOT NULL
);

CREATE TABLE [dbo].[ep_login_limits] (
 limit_key NCHAR(64) PRIMARY KEY,
 attempts INT NOT NULL,
 blocked_until BIGINT NOT NULL
);

CREATE TABLE [dbo].[ep_payroll] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 user_id BIGINT NOT NULL,
 month_key NCHAR(7) NOT NULL,
 calculation_json NVARCHAR(MAX) NOT NULL,
 net_amount DECIMAL(12,3) NOT NULL,
 status NVARCHAR(20) NOT NULL DEFAULT N'draft',
 created_at DATETIME2(0) NOT NULL,
 approved_by BIGINT NULL,
 approved_at DATETIME2(0) NULL,
 CONSTRAINT [ep_payroll_month] UNIQUE (user_id,month_key)
);

CREATE TABLE [dbo].[ep_cms_pages] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 slug NVARCHAR(80) NOT NULL UNIQUE,
 in_nav TINYINT NOT NULL DEFAULT 1,
 content_json NVARCHAR(MAX) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 visible TINYINT NOT NULL DEFAULT 1
);

CREATE TABLE [dbo].[ep_cms_sections] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 page_id BIGINT NOT NULL,
 template_key NVARCHAR(80) NOT NULL DEFAULT N'',
 layout NVARCHAR(20) NOT NULL DEFAULT N'grid',
 theme NVARCHAR(20) NOT NULL DEFAULT N'ivory',
 columns_count INT NOT NULL DEFAULT 3,
 content_json NVARCHAR(MAX) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 visible TINYINT NOT NULL DEFAULT 1
);

CREATE INDEX [ep_section_page] ON [dbo].[ep_cms_sections] (page_id);

CREATE TABLE [dbo].[ep_cms_cards] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 section_id BIGINT NOT NULL,
 template_key NVARCHAR(80) NOT NULL DEFAULT N'',
 card_type NVARCHAR(20) NOT NULL DEFAULT N'text',
 image_id BIGINT NULL,
 content_json NVARCHAR(MAX) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 visible TINYINT NOT NULL DEFAULT 1
);

CREATE INDEX [ep_card_section] ON [dbo].[ep_cms_cards] (section_id);

CREATE TABLE [dbo].[ep_messages] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 name NVARCHAR(150) NOT NULL,
 email NVARCHAR(180) NOT NULL,
 phone NVARCHAR(50) NOT NULL,
 body NVARCHAR(MAX) NOT NULL,
 created_at DATETIME2(0) NOT NULL,
 status NVARCHAR(20) NOT NULL DEFAULT N'new',
 ip_hash NCHAR(64) NOT NULL,
 department_id BIGINT NULL,
 kind NVARCHAR(30) NOT NULL DEFAULT N'general'
);

CREATE INDEX [ep_message_department] ON [dbo].[ep_messages] (department_id,status);

CREATE INDEX [ep_message_limit] ON [dbo].[ep_messages] (ip_hash,created_at);

CREATE TABLE [dbo].[ep_cms_media] (
 file_id BIGINT PRIMARY KEY,
 created_at DATETIME2(0) NOT NULL
);

CREATE TABLE [dbo].[ep_companies] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 code NVARCHAR(80) NOT NULL UNIQUE,
 name NVARCHAR(180) NOT NULL,
 legal_name NVARCHAR(250) NOT NULL DEFAULT N'',
 bank_name NVARCHAR(150) NOT NULL DEFAULT N'',
 bank_account NVARCHAR(100) NOT NULL DEFAULT N'',
 labor_file NVARCHAR(100) NOT NULL DEFAULT N'',
 active TINYINT NOT NULL DEFAULT 1,
 created_at DATETIME2(0) NOT NULL
);

CREATE TABLE [dbo].[ep_history] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 kind NVARCHAR(40) NOT NULL,
 employee_code NVARCHAR(80) NOT NULL,
 period_key NVARCHAR(100) NOT NULL,
 body_json NVARCHAR(MAX) NOT NULL,
 imported_at DATETIME2(0) NOT NULL,
 imported_by BIGINT NOT NULL,
 CONSTRAINT [ep_historical_key] UNIQUE (kind,employee_code,period_key)
);

CREATE TABLE [dbo].[ep_import_jobs] (
 token_hash NCHAR(64) PRIMARY KEY,
 user_id BIGINT NOT NULL,
 document_json NVARCHAR(MAX) NOT NULL,
 created_at DATETIME2(0) NOT NULL,
 used_at DATETIME2(0) NULL
);

CREATE TABLE [dbo].[ep_bank_payroll] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 user_id BIGINT NOT NULL,
 month_key NCHAR(7) NOT NULL,
 company_code NVARCHAR(80) NOT NULL,
 amount DECIMAL(12,3) NOT NULL,
 snapshot_json NVARCHAR(MAX) NOT NULL,
 status NVARCHAR(20) NOT NULL DEFAULT N'draft',
 created_at DATETIME2(0) NOT NULL,
 approved_at DATETIME2(0) NULL,
 CONSTRAINT [ep_bank_month] UNIQUE (user_id,month_key)
);

CREATE TABLE [dbo].[ep_customer_departments] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 code NVARCHAR(40) NOT NULL UNIQUE,
 content_json NVARCHAR(MAX) NOT NULL,
 active TINYINT NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0
);

CREATE TABLE [dbo].[ep_customer_members] (
 department_id BIGINT NOT NULL,
 user_id BIGINT NOT NULL,
 PRIMARY KEY(department_id,user_id)
);

CREATE TABLE [dbo].[ep_message_events] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 message_id BIGINT NOT NULL,
 user_id BIGINT NULL,
 event_type NVARCHAR(30) NOT NULL,
 from_department BIGINT NULL,
 to_department BIGINT NULL,
 note NVARCHAR(MAX) NOT NULL,
 created_at DATETIME2(0) NOT NULL
);

CREATE INDEX [ep_message_timeline] ON [dbo].[ep_message_events] (message_id,id);

CREATE TABLE [dbo].[ep_ratings] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 name NVARCHAR(150) NOT NULL,
 email NVARCHAR(180) NOT NULL,
 score TINYINT NOT NULL,
 body NVARCHAR(MAX) NOT NULL,
 consent_public TINYINT NOT NULL DEFAULT 0,
 status NVARCHAR(20) NOT NULL DEFAULT N'pending',
 reviewed_by BIGINT NULL,
 created_at DATETIME2(0) NOT NULL,
 ip_hash NCHAR(64) NOT NULL,
 phone NVARCHAR(50) NOT NULL DEFAULT N''
);

CREATE INDEX [ep_rating_public] ON [dbo].[ep_ratings] (status,consent_public);

CREATE INDEX [ep_rating_limit] ON [dbo].[ep_ratings] (ip_hash,created_at);

CREATE TABLE [dbo].[ep_translations] (
 hash NCHAR(64) PRIMARY KEY,
 target_lang NVARCHAR(12) NOT NULL,
 source_text NVARCHAR(MAX) NOT NULL,
 translated_text NVARCHAR(MAX) NOT NULL,
 created_at DATETIME2(0) NOT NULL
);

CREATE TABLE [dbo].[ep_translation_jobs] (
 id BIGINT IDENTITY(1,1) PRIMARY KEY,
 entity NVARCHAR(20) NOT NULL,
 entity_id BIGINT NOT NULL,
 target_lang NVARCHAR(12) NOT NULL,
 source_hash NCHAR(64) NOT NULL,
 source_json NVARCHAR(MAX) NOT NULL,
 status NVARCHAR(20) NOT NULL DEFAULT N'pending',
 error_text NVARCHAR(300) NOT NULL DEFAULT N'',
 created_at DATETIME2(0) NOT NULL,
 updated_at DATETIME2(0) NOT NULL
);

CREATE INDEX [ep_translation_queue] ON [dbo].[ep_translation_jobs] (status,id);
