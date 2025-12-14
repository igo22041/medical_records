-- Миграция для добавления поля attachment_file в таблицу medical_records
-- Выполните этот файл если база данных уже существует

USE medical_records;

ALTER TABLE medical_records 
ADD COLUMN attachment_file VARCHAR(255) NULL AFTER treatment,
ADD INDEX idx_attachment_file (attachment_file);

