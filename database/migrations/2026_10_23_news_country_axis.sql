-- Вторая ось разделов портала — страна. Рубрика у новости одна
-- (news.category_id), а стран может быть несколько: материал о торговле
-- Узбекистана и Казахстана принадлежит обеим лентам. Поэтому страна — та же
-- запись news_categories (slug, переводимое название, порядок, иконка), но с
-- осью `country`, а связь с новостью — отдельной таблицей.
ALTER TABLE news_categories
    ADD COLUMN axis ENUM('rubric','country') NOT NULL DEFAULT 'rubric' AFTER slug,
    ADD KEY idx_news_categories_axis (axis, is_active, sort_order);

CREATE TABLE IF NOT EXISTS news_countries (
    news_id     INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (news_id, category_id),
    KEY idx_news_countries_category (category_id, news_id),
    CONSTRAINT fk_news_countries_news
        FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    CONSTRAINT fk_news_countries_category
        FOREIGN KEY (category_id) REFERENCES news_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Разделы портала. Существующие записи с теми же slug не трогаются.
INSERT IGNORE INTO news_categories (name, slug, axis, is_active, sort_order) VALUES
    ('Новости', 'news', 'rubric', 1, 10),
    ('Отрасли', 'industries', 'rubric', 1, 20),
    ('Аналитика', 'analytics', 'rubric', 1, 30),
    ('Технологии', 'technology', 'rubric', 1, 40),
    ('Мероприятия', 'events', 'rubric', 1, 50),
    ('Маркетплейсы', 'marketplaces', 'rubric', 1, 60),
    ('Точка зрения', 'opinion', 'rubric', 1, 70),
    ('В мире', 'world', 'country', 1, 110),
    ('Узбекистан', 'uzbekistan', 'country', 1, 120),
    ('Казахстан', 'kazakhstan', 'country', 1, 130),
    ('Кыргызстан', 'kyrgyzstan', 'country', 1, 140),
    ('Таджикистан', 'tajikistan', 'country', 1, 150),
    ('Туркменистан', 'turkmenistan', 'country', 1, 160);

INSERT IGNORE INTO news_category_translations (category_id, lang, name)
SELECT c.id, t.lang, t.name
FROM news_categories c
JOIN (
    SELECT 'news' AS slug, 'uz' AS lang, 'Yangiliklar' AS name
    UNION ALL SELECT 'industries', 'uz', 'Tarmoqlar'
    UNION ALL SELECT 'analytics', 'uz', 'Tahlil'
    UNION ALL SELECT 'technology', 'uz', 'Texnologiyalar'
    UNION ALL SELECT 'events', 'uz', 'Tadbirlar'
    UNION ALL SELECT 'marketplaces', 'uz', 'Marketpleyslar'
    UNION ALL SELECT 'opinion', 'uz', 'Nuqtai nazar'
    UNION ALL SELECT 'world', 'uz', 'Dunyoda'
    UNION ALL SELECT 'uzbekistan', 'uz', 'Oʻzbekiston'
    UNION ALL SELECT 'kazakhstan', 'uz', 'Qozogʻiston'
    UNION ALL SELECT 'kyrgyzstan', 'uz', 'Qirgʻiziston'
    UNION ALL SELECT 'tajikistan', 'uz', 'Tojikiston'
    UNION ALL SELECT 'turkmenistan', 'uz', 'Turkmaniston'
    UNION ALL SELECT 'news', 'en', 'News'
    UNION ALL SELECT 'industries', 'en', 'Industries'
    UNION ALL SELECT 'analytics', 'en', 'Analytics'
    UNION ALL SELECT 'technology', 'en', 'Technology'
    UNION ALL SELECT 'events', 'en', 'Events'
    UNION ALL SELECT 'marketplaces', 'en', 'Marketplaces'
    UNION ALL SELECT 'opinion', 'en', 'Opinion'
    UNION ALL SELECT 'world', 'en', 'World'
    UNION ALL SELECT 'uzbekistan', 'en', 'Uzbekistan'
    UNION ALL SELECT 'kazakhstan', 'en', 'Kazakhstan'
    UNION ALL SELECT 'kyrgyzstan', 'en', 'Kyrgyzstan'
    UNION ALL SELECT 'tajikistan', 'en', 'Tajikistan'
    UNION ALL SELECT 'turkmenistan', 'en', 'Turkmenistan'
) t ON t.slug = c.slug;
