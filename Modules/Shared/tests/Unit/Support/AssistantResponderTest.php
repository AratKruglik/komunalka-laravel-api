<?php

declare(strict_types=1);

use Modules\Shared\Support\AssistantResponder;

beforeEach(function (): void {
    $this->responder = new AssistantResponder;
});

describe('AssistantResponder::respond', function (): void {
    it('matches the readings rule for показання/внести/зняти/квт keywords', function (string $question): void {
        $result = $this->responder->respond($question);

        expect($result['text'])->toBe('Щоб внести показання лічильника, перейдіть у розділ "Внести показання", оберіть лічильник і вкажіть нове значення.')
            ->and($result['sources'])->toBe([
                ['label' => 'Внести показання', 'path' => '/readings/create'],
            ]);
    })->with([
        'показання' => 'Як внести показання?',
        'внести' => 'Хочу внести нове значення',
        'зняти' => 'Як зняти показання лічильника?',
        'квт' => 'Скільки квт я витратив?',
    ]);

    it('matches the meters rule for лічильник keyword', function (): void {
        $result = $this->responder->respond('Як додати новий лічильник?');

        expect($result['text'])->toBe('Керувати лічильниками можна у розділі "Лічильники" — там ви можете додавати нові лічильники та переглядати історію показань.')
            ->and($result['sources'])->toBe([
                ['label' => 'Лічильники', 'path' => '/meters'],
            ]);
    });

    it('matches the providers rule for провайдер/постачальник keywords', function (string $question): void {
        $result = $this->responder->respond($question);

        expect($result['text'])->toBe('Обрати чи змінити постачальника послуг можна у розділі "Провайдери".')
            ->and($result['sources'])->toBe([
                ['label' => 'Провайдери', 'path' => '/providers'],
            ]);
    })->with([
        'провайдер' => 'Як обрати провайдера?',
        'постачальник' => 'Хто мій постачальник води?',
    ]);

    it('matches the addresses rule for адрес keyword', function (): void {
        $result = $this->responder->respond('Як додати нову адресу?');

        expect($result['text'])->toBe('Керувати вашими адресами можна у розділі "Мої адреси".')
            ->and($result['sources'])->toBe([
                ['label' => 'Мої адреси', 'path' => '/addresses'],
            ]);
    });

    it('matches the tariffs rule for тариф/ціна/вартість/розрах keywords', function (string $question): void {
        $result = $this->responder->respond($question);

        expect($result['text'])->toBe('Тарифи та розрахунки вартості послуг доступні у розділі "Провайдери".')
            ->and($result['sources'])->toBe([
                ['label' => 'Тарифи та розрахунки', 'path' => '/providers'],
            ]);
    })->with([
        'тариф' => 'Які зараз тарифи?',
        'ціна' => 'Яка ціна за воду?',
        'вартість' => 'Яка вартість послуг?',
        'розрах' => 'Як відбувається розрахунок?',
    ]);

    it('prioritizes the tariffs rule over the payments rule when "розрахунок" contains "рахунок" as a substring', function (): void {
        $result = $this->responder->respond('Як рахуються тарифи при розрахунку вартості?');

        expect($result['text'])->toBe('Тарифи та розрахунки вартості послуг доступні у розділі "Провайдери".')
            ->and($result['sources'])->toBe([
                ['label' => 'Тарифи та розрахунки', 'path' => '/providers'],
            ]);
    });

    it('matches the payments rule for оплата/рахунок/платіж keywords when tariff keywords are absent', function (string $question): void {
        $result = $this->responder->respond($question);

        expect($result['text'])->toBe('Інформацію про оплати та рахунки можна переглянути на дашборді.')
            ->and($result['sources'])->toBe([
                ['label' => 'Дашборд', 'path' => '/'],
            ]);
    })->with([
        'оплата' => 'Як здійснити оплату?',
        'рахунок' => 'Де подивитись мій рахунок?',
        'платіж' => 'Як зробити платіж?',
    ]);

    it('falls back to the default response when no keyword matches', function (): void {
        $result = $this->responder->respond('Розкажи анекдот про котиків');

        expect($result['text'])->toBe('Я поки не знаю точної відповіді на це запитання. Спробуйте переглянути дашборд або скористайтесь однією з підказок вище.')
            ->and($result['sources'])->toBe([
                ['label' => 'Дашборд', 'path' => '/'],
            ]);
    });

    it('is case-insensitive and trims whitespace before matching', function (): void {
        $result = $this->responder->respond('  ЛІЧИЛЬНИК  ');

        expect($result['sources'])->toBe([
            ['label' => 'Лічильники', 'path' => '/meters'],
        ]);
    });
});
