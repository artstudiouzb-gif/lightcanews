-- Портал файлов /repo удалён: у новостного портала его нет. Таблицы портала
-- уходят вместе с кодом; файлы, загруженные через него, на диске остаются —
-- их судьбу решает владелец, база о них больше не знает.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS repo_files;
DROP TABLE IF EXISTS repo_categories;
DROP TABLE IF EXISTS repo_users;
SET FOREIGN_KEY_CHECKS = 1;
