# Migration Plan: Claude Code to Gemini CLI SDLC

## Objective
Migrate the agentic SDLC setup from the reference Claude Code project (`~/Projects/Youtube/claude-laravel`) into the current workspace (`~/Projects/Youtube/Komunalka/komunalka-laravel-api`), adapt it for Gemini CLI, and enable the experimental browser agent.

## Background
The reference project has a mature agentic workflow (custom agents, skills, and global context in `CLAUDE.md`). This migration aims to replicate that power within Gemini CLI.

## Scope
*   **Subagents**: Migrating `.claude/agents/*.md` to `.gemini/agents/*.md`.
*   **Skills**: Migrating `.claude/skills/*` to `.gemini/skills/*`.
*   **Rules**: Migrating `.claude/rules/*.md` to `.gemini/rules/*.md`.
*   **Global Context**: Integrating `CLAUDE.md` into `GEMINI.md`.
*   **Configuration**: Enabling `browser_agent` in `~/.gemini/settings.json`.

## Implementation Steps
1.  **Copy Assets**: Copy `.claude/` and `CLAUDE.md` from the reference project to a local temporary folder.
2.  **Transformation**:
    *   Rename tool calls in Skill/Agent files: `Skill` -> `activate_skill`, `Read` -> `read_file`, `Write` -> `write_file`, `Edit` -> `replace`, `Bash` -> `run_shell_command`, `Grep` -> `grep_search`, `Glob` -> `glob`.
    *   Adapt agent frontmatter if necessary.
3.  **Deployment**: Move adapted files to `.gemini/agents/`, `.gemini/skills/`, and `.gemini/rules/`.
4.  **Context Integration**: Merge instructions from `CLAUDE_REF.md` into the local `GEMINI.md`.
5.  **Settings Update**: Modify `~/.gemini/settings.json` to enable the experimental browser agent.
6.  **Cleanup**: Remove temporary migration files.

## Verification
*   List `.gemini/agents` and `.gemini/skills` to confirm presence.
*   Check `~/.gemini/settings.json` for the browser agent flag.
*   Verify `GEMINI.md` content.