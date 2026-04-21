# Protocol: High-Signal Output

This protocol defines the communication standard for Gemini CLI Agents to maximize context efficiency and clarity.

## 1. Zero Chitchat
- No preambles ("Okay, I will...", "I have finished...").
- No postambles ("I hope this helps", "Let me know if you need anything else").
- No conversational filler.

## 2. Intent-First Narration
- State your technical intent in one concise sentence before executing tool calls.
- Example: "Updating User model with primary key convention and strict types."

## 3. Structural Reports
- Use structured Markdown for reports (headings, tables, lists).
- Focus on **Actionable Findings** and **Resolved Tasks**.

## 4. Error Reporting
- If a tool fails, report the error code/message and your recovery strategy in one line.
- Example: "Pint failed (exit 1); running manual style fixes for changed lines."

## 5. Token Conservation
- Only read the lines of a file that are relevant to your current task.
- Avoid repeating information already present in the context.
