<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

const HELP_FILE_INPUT = 'input[type="file"]';
const HELP_PAGE_PLACEHOLDER = '[placeholder="Запитайте про будь-який розділ Комуналки…"]';
const HELP_SEND_BUTTON = '[aria-label="Надіслати повідомлення"]';

function helpFixtureImage(string $name = 'meter.jpg'): string
{
    $directory = sys_get_temp_dir().'/help-browser-'.bin2hex(random_bytes(6));
    mkdir($directory);
    $path = $directory.'/'.$name;
    imagejpeg(imagecreatetruecolor(8, 8), $path);

    return $path;
}

function helpFixtureText(): string
{
    $directory = sys_get_temp_dir().'/help-browser-'.bin2hex(random_bytes(6));
    mkdir($directory);
    $path = $directory.'/notes.txt';
    file_put_contents($path, 'not an image');

    return $path;
}

/**
 * Replaces window.fetch so /help/ask answers with an approval card and
 * /help/approve answers according to the submitted action. Every request
 * is recorded in window.__helpCalls.
 */
function helpStubAssistantTransport(): string
{
    $approval = json_encode([
        'id' => 'approval-1',
        'tool' => 'record_meter_reading',
        'meter' => [
            'id' => 7,
            'name' => 'Кухня',
            'location' => null,
            'serial_number' => 'SN-42',
            'utility_type' => ['slug' => 'cold_water', 'name' => 'Холодна вода', 'unit' => 'м³'],
            'address' => 'вул. Тестова, 1',
        ],
        'reading_value' => 150.5,
        'reading_date' => '2026-01-15',
        'date_source' => 'exif',
        'exif_date' => '2026-01-15',
        'tariff' => null,
        'tariff_options' => [],
        'previous' => ['value' => 100, 'date' => '2025-12-15'],
        'consumption' => 50.5,
        'expires_at' => '2999-01-01T00:00:00+00:00',
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

    return <<<JS
        (() => {
            const approval = {$approval};
            const original = window.fetch.bind(window);
            window.__helpCalls = [];
            const json = (body) => new Response(JSON.stringify(body), {
                status: 200,
                headers: { 'Content-Type': 'application/json' },
            });
            window.fetch = async (url, init) => {
                const target = String(url);
                if (target.includes('/help/ask')) {
                    const isForm = init.body instanceof FormData;
                    window.__helpCalls.push({
                        endpoint: 'ask',
                        isForm,
                        hasPhoto: isForm && init.body.has('photo'),
                    });
                    return json({
                        text: 'Бачу показання 150,5. Перевірте та підтвердіть.',
                        sources: [],
                        conversation_id: 'conversation-1',
                        pending_approval: approval,
                        reading_result: null,
                    });
                }
                if (target.includes('/help/approve')) {
                    const body = JSON.parse(init.body);
                    window.__helpCalls.push({ endpoint: 'approve', body });
                    const reply = { sources: [], conversation_id: 'conversation-1', pending_approval: null };
                    if (body.action === 'reject') {
                        return json({
                            ...reply,
                            text: 'Запис показання скасовано. Нічого не збережено.',
                            reading_result: { status: 'rejected', message: 'Запис показання скасовано.', reading: null },
                        });
                    }
                    const value = body.arguments?.reading_value ?? 150.5;
                    return json({
                        ...reply,
                        text: 'Готово, показання внесено.',
                        reading_result: {
                            status: 'saved',
                            message: 'Показання збережено.',
                            reading: { id: 1, meter_id: 7, reading_value: value, reading_date: '2026-01-15', consumption: value - 100 },
                        },
                    });
                }
                return original(url, init);
            };
        })()
        JS;
}

function helpApprovalCalls(mixed $page): array
{
    return json_decode((string) $page->script('JSON.stringify(window.__helpCalls)'), true, flags: JSON_THROW_ON_ERROR);
}

function helpOpenApprovalCard(): mixed
{
    $page = visit(route('help.index'));
    $page->script(helpStubAssistantTransport());

    return $page->attach(HELP_FILE_INPUT, helpFixtureImage())
        ->click(HELP_SEND_BUTTON)
        ->assertSee('Перевірте показання');
}

describe('Help chat photo attachment', function (): void {
    beforeEach(function (): void {
        $this->actingAs(User::factory()->create());
    });

    it('shows the attached photo with the privacy notice and lets the user remove it', function (): void {
        $page = visit(route('help.index'))
            ->assertSee('Запит обробляє AI-провайдер')
            ->attach(HELP_FILE_INPUT, helpFixtureImage('lichylnyk.jpg'))
            ->assertSee('lichylnyk.jpg')
            ->assertSee('Фото надсилається AI-провайдеру для розпізнавання')
            ->assertNoJavaScriptErrors();

        $page->click('[aria-label="Прибрати фото"]')
            ->assertDontSee('lichylnyk.jpg')
            ->assertSee('Запит обробляє AI-провайдер')
            ->assertNoJavaScriptErrors();
    });

    it('rejects an unsupported file type on the client', function (): void {
        visit(route('help.index'))
            ->attach(HELP_FILE_INPUT, helpFixtureText())
            ->assertSee('Фото має бути у форматі JPG, PNG, WebP або HEIC.')
            ->assertDontSee('notes.txt')
            ->assertNoJavaScriptErrors();
    });
});

describe('Help chat meter approval card', function (): void {
    beforeEach(function (): void {
        $this->actingAs(User::factory()->create());
    });

    it('renders the proposed reading with previous value and consumption', function (): void {
        helpOpenApprovalCard()
            ->assertSee('Кухня')
            ->assertSee('Холодна вода')
            ->assertSee('з EXIF')
            ->assertSee('Попереднє')
            ->assertSee('Споживання')
            ->assertSee('Підтвердити')
            ->assertSee('Виправити')
            ->assertSee('Скасувати')
            ->assertNoJavaScriptErrors();
    });

    it('uploads the photo as multipart form data', function (): void {
        $page = helpOpenApprovalCard();

        expect(helpApprovalCalls($page)[0])->toMatchArray(['endpoint' => 'ask', 'isForm' => true, 'hasPhoto' => true]);
    });

    it('confirms the reading and shows the saved result without further actions', function (): void {
        $page = helpOpenApprovalCard()
            ->click('Підтвердити')
            ->assertSee('Показання збережено')
            ->assertSee('Переглянути показання')
            ->assertDontSee('Виправити')
            ->assertNoJavaScriptErrors();

        expect(helpApprovalCalls($page)[1])->toBe(['endpoint' => 'approve', 'body' => [
            'conversation_id' => 'conversation-1',
            'approval_id' => 'approval-1',
            'action' => 'approve',
        ]]);
    });

    it('cancels the reading and saves nothing', function (): void {
        $page = helpOpenApprovalCard()
            ->click('Скасувати')
            ->assertSee('Нічого не збережено')
            ->assertDontSee('Показання збережено')
            ->assertNoJavaScriptErrors();

        expect(helpApprovalCalls($page)[1]['body']['action'])->toBe('reject')
            ->and($page->script("[...document.querySelectorAll('button')].some((button) => button.textContent.trim() === 'Підтвердити')"))->toBeFalse();
    });

    it('sends only the changed field when the user edits the value', function (): void {
        $page = helpOpenApprovalCard()
            ->click('Виправити')
            ->assertSee('Зберегти зміни')
            ->fill('input[inputmode="decimal"]', '160')
            ->click('Зберегти зміни')
            ->assertSee('Показання збережено')
            ->assertNoJavaScriptErrors();

        $approve = helpApprovalCalls($page)[1]['body'];

        expect($approve['action'])->toBe('edit')
            ->and($approve['arguments'])->toBe(['reading_value' => 160]);
    });

    it('blocks an invalid edited value without calling the server', function (): void {
        $page = helpOpenApprovalCard()
            ->click('Виправити')
            ->fill('input[inputmode="decimal"]', 'abc')
            ->click('Зберегти зміни')
            ->assertSee('Вкажіть коректне значення показника')
            ->assertDontSee('Показання збережено')
            ->assertNoJavaScriptErrors();

        expect(array_column(helpApprovalCalls($page), 'endpoint'))->toBe(['ask']);
    });

    it('marks the card stale when the user sends a new message', function (): void {
        helpOpenApprovalCard()
            ->fill(HELP_PAGE_PLACEHOLDER, 'Як обрати провайдера послуг?')
            ->click(HELP_SEND_BUTTON)
            ->assertSee('Запит більше неактуальний')
            ->assertNoJavaScriptErrors();
    });
});

describe('Help chat widget persistence across navigation', function (): void {
    it('keeps the open widget and its conversation after an Inertia visit', function (): void {
        $this->actingAs(User::factory()->create());

        visit(route('dashboard'))
            ->click('[aria-label="Відкрити чат допомоги"]')
            ->click('Як внести показання?')
            ->assertSee('Щоб внести показання лічильника, перейдіть у розділ')
            ->click('a[href$="/meters"]')
            ->assertPathContains('/meters')
            ->assertSee('Щоб внести показання лічильника, перейдіть у розділ')
            ->assertNoJavaScriptErrors();
    });

    it('does not mount the widget on guest pages', function (): void {
        visit(route('login'))
            ->assertPresent('input[type="email"]')
            ->assertMissing('[aria-label="Відкрити чат допомоги"]')
            ->assertNoJavaScriptErrors();
    });
});
