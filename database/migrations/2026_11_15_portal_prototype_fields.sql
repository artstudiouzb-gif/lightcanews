-- Поля, которых требует прототип главной LightCA.News.
-- Автор колонки «Точки зрения»: имя, должность и фото. Пользователи панели —
-- это редакторы, а колонку пишет внешний эксперт, поэтому поля у новости.
ALTER TABLE news
    ADD COLUMN author_name VARCHAR(120) NULL AFTER is_partner,
    ADD COLUMN author_role VARCHAR(160) NULL AFTER author_name,
    ADD COLUMN author_photo VARCHAR(500) NULL AFTER author_role;

-- Факт на фото страны в «Нитях региона» («+18% экспорт текстиля»).
ALTER TABLE news_categories
    ADD COLUMN fact VARCHAR(80) NULL AFTER icon;
ALTER TABLE news_category_translations
    ADD COLUMN fact VARCHAR(80) NULL AFTER name;
