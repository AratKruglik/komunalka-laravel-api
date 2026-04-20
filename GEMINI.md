# Gemini Instructions (Index)

> Canonical AI Agent Guidelines for Komunalka — Laravel 13 API + Inertia v3 + React 19.
> This file is auto-loaded by Gemini CLI; contents of `@`-referenced rules are merged in.

## Gemini-Specific Behavior

- Prefer a Skill over repeating rules inline. Activate skills from `.gemini/skills/`.
- Respond in Ukrainian. Code identifiers stay in their original form.

## Rules (auto-loaded)

@.gemini/rules/workflow.md
@.gemini/rules/code-style.md
@.gemini/rules/architecture.md
@.gemini/rules/git-operations.md
@.gemini/rules/docker-commands.md
@.gemini/rules/forms-authorization.md
@.gemini/rules/inertia-react.md
@.gemini/rules/migrations-queue.md
@.gemini/rules/testing.md
@.gemini/rules/mcp-stack.md

## IMPORTANT

1. Before writing any code, describe your approach and wait for approval.
2. If requirements are ambiguous, ask clarifying questions before writing code.
3. After finishing code, list edge cases and suggest test cases.
4. If a task requires changes to more than 3 files, stop and break it into smaller tasks.
5. When there's a bug, start by writing a test that reproduces it, then fix it.
6. After every user correction: reflect on what went wrong and update `docs/tasks-docs/lessons.md`.

## Core Principles

- **Simplicity First** — every change as simple as possible, minimal blast radius
- **No Laziness** — root-cause fixes, senior-level standards, no temporary workarounds
- **Minimal Impact** — touch only what's necessary; no drive-by refactors

## Agent Dispatch (MANDATORY)

- **ALWAYS** follow the pipeline in `@.gemini/rules/workflow.md`
- **ALWAYS** run independent pipeline steps in parallel (fan-out via multiple `@agent-name` invocations)
- **ALWAYS** autonomously decide which agents to dispatch — do NOT ask the user to pick
- For agent roster and routing table → see "Agent Quick Routing" in `@.gemini/rules/workflow.md`

## Tech Stack (Short)

- **Backend**: Laravel 13, PHP 8.4, Octane + FrankenPHP, JWT (php-open-source-saver/jwt-auth), Socialite, Spatie Media Library, Reverb
- **Modules (nwidart/laravel-modules)**: Shared, Auth, Address, Meter, Billing, Export
- **Frontend**: Inertia v3 (`@inertiajs/react`), React 19 (TSX, strict TS), Tailwind v4 + Tailwind Variants (`tv()`), Ziggy v2
- **Data**: PostgreSQL 17, Redis 7.2+
- **Testing**: Pest 4 (unit/feature/browser), PHPUnit 12, Infection (mutation, min 100%)
- **Package Managers**: composer, pnpm

## Docker-Only Invocation

All backend commands run in containers (service name is `app`, never `api`).
See `@.gemini/rules/docker-commands.md` for the full command reference.

## Task Management

1. **Plan First** — write plan to `docs/tasks-docs/todo.md` with checkable items
2. **Verify Plan** — check in before starting implementation
3. **Track Progress** — mark items complete as you go
4. **Explain Changes** — high-level summary at each step
5. **Document Results** — add review section to `docs/tasks-docs/todo.md`
6. **Capture Lessons** — update `docs/tasks-docs/lessons.md` after corrections
