<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Предупреждения редактору о полях, которые заполнены, но не сработают.
 *
 * Блоки местами молча игнорируют ввод: кнопка без ссылки не выводится, ручные
 * карточки не показываются при автоматическом источнике, слайд без фотографии
 * отбрасывается при сохранении. Раньше об этом можно было узнать, только
 * открыв сайт и не найдя своего текста.
 */
final class BlockHints
{
    /**
     * @param array<string,mixed> $data данные блока после нормализации
     * @return list<string> сообщения для показа редактору
     */
    public static function forBlock(string $type, array $data): array
    {
        $hints = [];
        $filled = static fn (string $key): bool => trim((string) ($data[$key] ?? '')) !== '';

        // Кнопка выводится только когда есть и подпись, и ссылка.
        foreach ([
            ['button_text', 'button_url', 'Кнопка'],
            ['button2_text', 'button2_url', 'Вторая кнопка'],
            ['all_text', 'all_url', 'Ссылка «Все…»'],
            ['cta_button_text', 'cta_button_url', 'Кнопка в блоке призыва'],
            ['video_button_text', 'video_button_url', 'Кнопка видео'],
        ] as [$textKey, $urlKey, $label]) {
            if (!$filled($textKey) || $filled($urlKey)) {
                continue;
            }
            // У «Подписки» это подпись кнопки отправки формы, ссылка ей не нужна.
            if ($textKey === 'button_text' && $type === 'subscribe') {
                continue;
            }
            // Блоки-обёртки сами подставляют ссылку на раздел при пустом поле.
            if ($textKey === 'all_text' && self::fillsSectionLink($type, $data)) {
                continue;
            }
            $hints[] = $label . ': подпись задана, но не указана ссылка — на сайте она не появится.';
        }

        // Обратный случай у «Контактов»: адрес без подписи шаблон не выводит.
        // Так бывает после смены типа из «Карточек», где ссылкой была вся
        // карточка, — адрес сохранён, но на сайте его не видно.
        if ($type === 'contact_cards') {
            foreach (array_values((array) ($data['items'] ?? [])) as $i => $item) {
                if (is_array($item) && trim((string) ($item['link_url'] ?? '')) !== '' && trim((string) ($item['link_text'] ?? '')) === '') {
                    $hints[] = 'Контакт ' . ($i + 1) . ': ссылка указана без подписи — на сайте она не появится. Задайте подпись ссылки.';
                }
            }
        }

        // Цитата в коллаже стоит в ячейке фиксированной высоты, и текст,
        // который туда не помещается, гасится маской — обрыв виден, но почему
        // он случился, из редактора не видно: ячейка задаётся раскладкой, а
        // длина текста — полем в другом месте формы. Считать, сколько строк
        // влезет, сервер не может (это зависит от ширины экрана), поэтому
        // подсказка срабатывает на явном перекосе: невысокая ячейка и текст
        // длиннее пары строк.
        if ($type === 'collage') {
            // Композиция по ролям берёт не все элементы, и какие именно —
            // из формы не видно: редактору говорится прямо, что пропадёт.
            $layout = (string) ($data['layout'] ?? '');
            $hints = array_merge($hints, \App\Core\CollageComposition::problems(
                $layout,
                array_values(array_filter((array) ($data['items'] ?? []), 'is_array'))
            ));
            // У композиций нет ячеек, и «невысокая ячейка» там не бывает.
            foreach (\App\Core\CollageLayout::isComposed($layout) ? [] : (array) ($data['items'] ?? []) as $item) {
                if (!is_array($item) || ($item['type'] ?? '') !== 'quote') {
                    continue;
                }
                $length = mb_strlen(trim((string) ($item['quote_text'] ?? '')));
                if ((int) ($item['row_span'] ?? 1) <= 1 && $length > 90) {
                    $hints[] = 'Цитата длиннее, чем помещается в свою ячейку: на сайте будут видны первые строки, остальные погаснут. '
                        . 'Сократите текст или дайте элементу больше высоты.';
                    break;
                }
            }
        }

        // Врезка опознаёт источник по ссылке: незнакомый адрес не выводится
        // вовсе, и без подсказки редактор видел бы пустое место без причины.
        if ($type === 'embed' && $filled('url') && \App\Core\EmbedSource::parse((string) $data['url']) === null) {
            $hints[] = 'Ссылка не распознана — на сайте врезки не будет.'
                . ' Поддерживаются ролик YouTube, пост Telegram (t.me/канал/номер) и форма Google.';
        }

        // Выбор по ссылкам берёт посты только из сохранённой копии ленты:
        // отдельный пост по адресу API не отдаёт. Ссылка мимо копии молча
        // пропала бы с сайта, поэтому её называем.
        if ($type === 'instagram_feed' && ($data['order'] ?? 'date') === 'pick') {
            $missing = \App\Core\InstagramFeed::picked((string) ($data['links'] ?? ''))['missing'];
            if (!$filled('links')) {
                $hints[] = 'Выбран показ по ссылкам, но ссылок нет — блок на сайте не появится.';
            } elseif ($missing !== []) {
                $hints[] = 'Этих публикаций нет в сохранённой копии ленты, на сайте их не будет: '
                    . implode(', ', array_slice($missing, 0, 5)) . (count($missing) > 5 ? ' …' : '')
                    . '. В копии хранятся только последние публикации (сколько — настройка в «Соцсети → Лента на сайте», до '
                    . \App\Core\InstagramFeed::MAX_POSTS . '); обновите ленту или проверьте ссылку.';
            }
        }

        // Автоматический источник подменяет ручной список целиком.
        if (in_array($type, ['cards_grid', 'media_gallery'], true)) {
            $source = (string) ($data['source'] ?? 'manual');
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            if ($source !== 'manual' && $items !== []) {
                $hints[] = 'Выбран автоматический источник, поэтому добавленные вручную элементы ('
                    . count($items) . ') не показываются. Выберите источник «Вручную» или очистите список.';
            }
        }

        // Слайд без фотографии отбрасывается при сохранении.
        if ($type === 'slider') {
            $slides = is_array($data['slides'] ?? null) ? $data['slides'] : [];
            $empty = 0;
            foreach ($slides as $slide) {
                if (trim((string) (is_array($slide) ? ($slide['image'] ?? '') : '')) === '') {
                    $empty++;
                }
            }
            if ($empty > 0) {
                $hints[] = 'Слайдов без изображения: ' . $empty . '. Такие слайды не сохраняются — добавьте фото.';
            }
        }

        // Фон-фотография грузится сразу, без ленивой загрузки: тяжёлый файл
        // тормозит первую отрисовку, а редактор об этом не узнает.
        $backgroundHint = self::heavyBackgroundHint($data);
        if ($backgroundHint !== null) {
            $hints[] = $backgroundHint;
        }

        // Блок формы без выбранной формы виден только как заглушка.
        if ($type === 'form' && (int) ($data['form_id'] ?? 0) === 0) {
            $hints[] = 'Форма не выбрана — на сайте вместо неё будет предупреждение. Выберите форму в поле блока.';
        }

        return $hints;
    }

    /**
     * Вес фотографии-фона. Порог — 400 КБ: столько «стоит» приличный кадр
     * после сжатия, дальше начинается заметная задержка первой отрисовки.
     *
     * @param array<string,mixed> $data
     */
    private static function heavyBackgroundHint(array $data): ?string
    {
        if (($data['_bg_mode'] ?? '') !== 'image') {
            return null;
        }
        $url = trim((string) ($data['_bg_image'] ?? ''));
        if ($url === '') {
            return null;
        }

        $prefix = rtrim((string) Config::get('paths.public_uploads_url', '/uploads/public'), '/');
        $base = rtrim((string) Config::get('paths.public_uploads', ''), '/');
        if ($base === '' || !str_starts_with($url, $prefix . '/')) {
            return null;
        }

        $path = $base . substr(preg_replace('/[?#].*$/', '', $url) ?? $url, strlen($prefix));
        if (!is_file($path)) {
            return null;
        }

        $size = (int) filesize($path);
        if ($size <= 400 * 1024) {
            return null;
        }

        return 'Фон секции весит ' . round($size / 1048576, 1) . ' МБ. Фон не грузится лениво: '
            . 'страница будет ждать этот файл. Уменьшите кадр или возьмите более сжатый.';
    }

    /**
     * Подставляет ли блок ссылку «Все…» сам (BlockRenderer::enrichData).
     *
     * @param array<string,mixed> $data
     */
    private static function fillsSectionLink(string $type, array $data): bool
    {
        if (in_array($type, ['news_latest', 'news_feature', 'news_front', 'news_docs'], true)) {
            return true;
        }
        // Лента Instagram ведёт на профиль из своих настроек — если имя
        // аккаунта там уже есть (его подставляет и первый проход импорта).
        if ($type === 'instagram_feed') {
            return InstagramFeed::profileUrl() !== '';
        }
        $source = (string) ($data['source'] ?? 'manual');

        return ($type === 'cards_grid' && ($data['variant'] ?? 'icon') === 'image' && $source === 'projects')
            || ($type === 'media_gallery' && $source === 'albums');
    }

    /**
     * Пустой ли блок после сохранения: такой не выводится на сайте, и редактору
     * стоит сказать об этом сразу, а не оставлять гадать.
     *
     * @param array<string,mixed> $block строка блока (id/type/data)
     */
    public static function rendersEmpty(array $block): bool
    {
        $rendered = BlockRenderer::render($block);

        return empty($rendered['hidden']) && BlockRenderer::isVisuallyEmpty((string) $rendered['html']);
    }
}
