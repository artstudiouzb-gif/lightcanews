-- «Проекты» удалены: у новостного портала этого раздела нет. Проект был
-- строкой pages с entity_type = 'project'; его блоки, переводы полей и
-- ревизии блоков уходят каскадом. Колонка entity_type остаётся: по ней
-- фильтруют выборки страниц, и смена ENUM ничего бы не дала.
DELETE FROM content_revisions WHERE entity_type = 'project';
DELETE FROM pages WHERE entity_type = 'project';

-- Блок и виджет «Список проектов» больше не рисуются.
DELETE FROM blocks WHERE type = 'projects_list';
DELETE FROM widgets WHERE type = 'projects_list';

-- Страница-шапка раздела /projects снова обычная страница.
UPDATE pages SET section = '' WHERE section = 'projects';

-- Пункты меню на проекты и на раздел вели бы на 404.
DELETE FROM menu_items WHERE (url_type = 'page' AND url_value LIKE 'projects/%')
    OR (url_type = 'custom' AND (url_value = '/projects' OR url_value LIKE '/projects/%'));
