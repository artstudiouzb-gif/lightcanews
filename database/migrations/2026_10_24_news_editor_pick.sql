-- «Выбор редакции» — ручная отметка у новости, а не подборка по просмотрам:
-- редакция сама решает, что стоит прочитать. Отметка общая для группы
-- перевода (её ставит форма сразу всем языковым версиям).
ALTER TABLE news
    ADD COLUMN is_editor_pick TINYINT(1) NOT NULL DEFAULT 0 AFTER views,
    ADD KEY idx_news_editor_pick (is_editor_pick, status, published_at);
