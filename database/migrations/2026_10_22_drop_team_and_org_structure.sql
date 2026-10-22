-- «Команда», «Карточка руководителя» и «Оргструктура» удалены: у новостного
-- портала нет ни раздела сотрудников, ни схемы подчинения. Блоки этих типов
-- уходят вместе с таблицами — без шаблона они вышли бы комментарием
-- «Неизвестный тип блока». Фотографии сотрудников остаются в медиатеке.
DELETE FROM blocks WHERE type IN ('team_list', 'leader_card', 'org_structure');
DELETE FROM widgets WHERE type = 'team_list';
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS team_member_translations;
DROP TABLE IF EXISTS team_members;
SET FOREIGN_KEY_CHECKS = 1;
