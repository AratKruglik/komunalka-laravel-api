<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

class AssistantResponder
{
    /**
     * Ordered list of `[patterns[], text, sourceLabel, sourcePath]` tuples.
     *
     * Iterated top-to-bottom, first match wins. This MUST stay an ordered (numerically
     * indexed) list rather than an associative array: order is semantically load-bearing
     * (e.g. "тариф"/"розрах" must be checked before "рахунок", otherwise a question about
     * "розрахунок тарифів" would incorrectly match the payments rule via the "рахунок"
     * substring inside "розрахунок").
     *
     * @var list<array{0: list<string>, 1: string, 2: string, 3: string}>
     */
    private const array RULES = [
        [
            ['показанн', 'внест', 'знят', 'квт'],
            'Щоб внести показання лічильника, перейдіть у розділ "Внести показання", оберіть лічильник і вкажіть нове значення.',
            'Внести показання',
            '/readings/create',
        ],
        [
            ['лічильник'],
            'Керувати лічильниками можна у розділі "Лічильники" — там ви можете додавати нові лічильники та переглядати історію показань.',
            'Лічильники',
            '/meters',
        ],
        [
            ['провайдер', 'постачальн'],
            'Обрати чи змінити постачальника послуг можна у розділі "Провайдери".',
            'Провайдери',
            '/providers',
        ],
        [
            ['адрес'],
            'Керувати вашими адресами можна у розділі "Мої адреси".',
            'Мої адреси',
            '/addresses',
        ],
        [
            ['тариф', 'ціна', 'вартіст', 'розрах'],
            'Тарифи та розрахунки вартості послуг доступні у розділі "Провайдери".',
            'Тарифи та розрахунки',
            '/providers',
        ],
        [
            ['оплат', 'рахунок', 'платіж'],
            'Інформацію про оплати та рахунки можна переглянути на дашборді.',
            'Дашборд',
            '/',
        ],
    ];

    private const string FALLBACK_TEXT = 'Я поки не знаю точної відповіді на це запитання. Спробуйте переглянути дашборд або скористайтесь однією з підказок вище.';

    private const string FALLBACK_LABEL = 'Дашборд';

    private const string FALLBACK_PATH = '/';

    /** @return array{text: string, sources: list<array{label: string, path: string}>} */
    public function respond(string $question): array
    {
        $normalized = mb_strtolower(trim($question));

        foreach (self::RULES as [$patterns, $text, $sourceLabel, $sourcePath]) {
            foreach ($patterns as $pattern) {
                if (str_contains($normalized, $pattern)) {
                    return [
                        'text' => $text,
                        'sources' => [
                            ['label' => $sourceLabel, 'path' => $sourcePath],
                        ],
                    ];
                }
            }
        }

        return [
            'text' => self::FALLBACK_TEXT,
            'sources' => [
                ['label' => self::FALLBACK_LABEL, 'path' => self::FALLBACK_PATH],
            ],
        ];
    }
}
