# Plan: SDLC Orchestration Optimization

Transform the current multi-agent setup into a highly efficient, automated SDLC pipeline integrated with Conductor.

## Goal
Improve context efficiency, reduce tool hallucinations, and automate the Quality Gate process for the "Komunalka" project.

## Tasks

### Phase 1: Foundation & Tooling (Meta-Optimization)
- [ ] Create `protocols/*.md` for shared agent behaviors (FS safety, response style).
- [ ] Update `settings.json` with strict `allowedTools` for each agent to prevent hallucination.
- [ ] Reconfigure models: use `gemini-2.0-flash` for `reviewer`, `tester`, `security-scanner`.
- [ ] Add `rule-tagging` (metadata) to all files in `rules/` for selective loading.

### Phase 2: Agent Refinement (Specialization)
- [ ] Split `developer.md` into `backend-developer.md` and `frontend-developer.md`.
- [ ] Inject Conductor context into `ba.md` and `docs-writer.md` (track awareness).
- [ ] Update `ddd-architect.md` to use ADR templates from `skills/architecture-designer`.

### Phase 3: Automation (Pipeline)
- [ ] Implement `commands/qa-gate.toml` for parallel validation fan-out.
- [ ] Create `commands/track-sync.toml` to link Gemini TODOs with Conductor plans.
- [ ] Optimize `hooks/pint-rector.sh` (trigger on task completion, not file edit).

### Phase 4: Knowledge Management
- [ ] Automate `docs/tasks-docs/lessons.md` updates via `docs-writer`.
- [ ] Create standard PR templates that exclude AI mentions as per project rules.

## Done When
- [ ] Agents use 30% less context on average.
- [ ] `/qa-gate` produces a consolidated report from 4 agents.
- [ ] Conductor tracks are automatically updated by agents.
- [ ] Developer agents are focused on their specific layers (BE/FE).
