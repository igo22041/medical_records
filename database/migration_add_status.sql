-- Миграция: Добавление поля статуса болезни
USE medical_records;

ALTER TABLE medical_records 
ADD COLUMN status ENUM('active', 'in_treatment', 'recovered', 'chronic', 'cancelled') 
DEFAULT 'active' 
AFTER record_date;

CREATE INDEX idx_status ON medical_records(status);




