<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Laravel\Ai\Classification;
use Laravel\Ai\Classification\Choice;
use Laravel\Ai\Responses\Data\ChoiceAnswer;
use Modules\Ai\DTO\HelpIntentDecision;
use Modules\Ai\Enums\HelpIntent;
use Modules\Ai\Enums\HelpWorkflow;
use Throwable;

class ClassifyHelpIntent
{
    private const array OPTIONS = [
        'meter_reading' => 'Користувач хоче внести або уточнити показання лічильника.',
        'property_setup' => 'Користувач хоче створити адресу, провайдера або лічильник.',
        'product_support' => 'Користувач питає, як працює застосунок.',
        'clarify' => 'Запит неоднозначний або поєднує кілька сценаріїв без ясного порядку.',
    ];

    public function handle(string $message, ?HelpWorkflow $workflow = null, ?string $summary = null): HelpIntentDecision
    {
        $message = trim($message);
        $minimum = config('ai-support.router_min_probability');
        $margin = config('ai-support.router_min_margin');

        if ($message === '' || ! is_numeric($minimum) || ! is_numeric($margin)) {
            return new HelpIntentDecision(HelpIntent::Clarify, 'unconfigured');
        }

        try {
            $response = Classification::of([
                'message' => $message,
                'active_workflow' => $workflow?->value,
                'context' => $summary === null ? null : mb_substr($summary, 0, 200),
            ])->question('intent', new Choice(
                'Визнач один намір українського повідомлення для маршрутизації ХаткоБота.',
                self::OPTIONS,
            ))->timeout(10)->classify(
                provider: 'openrouter',
                model: (string) config('ai-support.router_model', 'typesafe/jev-1.14'),
            );

            $answer = $response->answers['intent'] ?? null;
        } catch (Throwable) {
            return new HelpIntentDecision(HelpIntent::Clarify, 'provider_failure');
        }

        if (! $answer instanceof ChoiceAnswer || $answer->confidence === null || ! is_finite($answer->confidence)
            || $answer->confidence < 0 || $answer->confidence > 1
            || count($answer->probabilities) !== count(self::OPTIONS)
            || array_diff_key(self::OPTIONS, $answer->probabilities) !== []) {
            return new HelpIntentDecision(HelpIntent::Clarify, 'invalid_answer');
        }

        foreach ($answer->probabilities as $probability) {
            if (! is_finite($probability) || $probability < 0 || $probability > 1) {
                return new HelpIntentDecision(HelpIntent::Clarify, 'invalid_answer');
            }
        }

        if (abs(array_sum($answer->probabilities) - 1.0) > 0.02) {
            return new HelpIntentDecision(HelpIntent::Clarify, 'invalid_answer');
        }

        $intent = HelpIntent::tryFrom($answer->choice);
        $probabilities = $answer->probabilities;
        arsort($probabilities);
        $ranked = array_keys($probabilities);

        if ($intent === null || $ranked[0] !== $answer->choice
            || $probabilities[$ranked[0]] < (float) $minimum
            || (float) $margin > $probabilities[$ranked[0]] - $probabilities[$ranked[1]]) {
            return new HelpIntentDecision(HelpIntent::Clarify, 'uncertain');
        }

        return new HelpIntentDecision($intent, 'classified');
    }
}
