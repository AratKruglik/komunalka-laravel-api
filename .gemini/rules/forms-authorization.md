# Form Requests & Authorization

## Form Request

All form submissions require a Form Request. Never validate in the Action body.

- `authorize()` returns `true` — auth check is done via Policy in the Action
- `rules()` returns typed `array<string, array<string>>`
- Check sibling Form Requests to follow existing array-vs-string rule conventions

> For `StoreMeterReadingRequest` template: activate skill `laravel-actions-patterns`.

## Authorization in Actions

Use `authorize()` method in `AsController` actions for Policy checks:

```php
public function authorize(UpdateMeterRequest $request, Meter $meter): bool
{
    return $request->user()->can('update', $meter);
}
```

## Policy Pattern

Policies live in `Modules/{Domain}/Policies/`. Register via `Gate::policy()` in the module's service provider.

Policy methods receive the authenticated `User` and the model instance. Use `$model->getKey()` for comparison — never `$model->id`.

> For full Policy and Form Request code examples: activate skill `laravel-actions-patterns`.

## Validation Error Flow

Laravel Form Request errors → `$page.props.errors` in Inertia → `useForm().errors.field` in React (`@inertiajs/react`).
