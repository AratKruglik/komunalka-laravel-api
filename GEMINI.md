# Gemini CLI Project Configuration — Komunalka

## SDLC Framework: Conductor Bridge
This project uses **Conductor Tracks** to orchestrate the SDLC. All implementation MUST be managed through tracks.

## Agent Orchestration
Implementation is handled by specialized subagents using defined pipelines:
1. **Requirements/Initial Analysis**: `@ba` (Track Architect)
2. **Architecture**: `@ddd-architect`
3. **Execution**: `@developer`
4. **Quality Audit**: `/qa-gate` (Parallel fan-out)
5. **Finalization**: `/ship`

## Core Protocols
All agents MUST strictly follow:
- `@.gemini/protocols/conductor-bridge.md`: Track & Plan synchronization.
- `@.gemini/protocols/high-signal-output.md`: Concise communication.

## Rules & Conventions
Rules are loaded selectively by agents. Reference the following for domain knowledge:
- Backend: `.gemini/rules/architecture.md`, `.gemini/rules/code-style.md`
- Frontend: `.gemini/rules/inertia-react.md`
- DB: `.gemini/rules/migrations-queue.md`
- Testing: `.gemini/rules/testing.md`

## Docker-Only Invocation
All backend commands MUST run in containers (`app` service).
See `.gemini/rules/docker-commands.md` for the full reference.
