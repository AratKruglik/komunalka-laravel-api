# Design System Inspired by ElevenLabs

## 1. Visual Theme & Atmosphere

ElevenLabs is a voice AI platform with a stark, minimal identity. Pure black and white with golden yellow as the sole accent creates a focused, studio-like environment. The design keeps the interface out of the way so audio — waveforms, voice clones, studio timelines — can be the hero.

**Key Characteristics:**
- Golden yellow on pure black — stark and memorable
- Audio-first: waveforms and playback as primary UI elements
- Extreme minimalism — zero decorative elements
- Inter for sharp, modern technical clarity

## 2. Color Palette & Roles

### Primary
- **Gold** (`#F5C542`): Active playback, selected states, brand accent
- **Gold Dark** (`#D4A820`): Hover state

### Accent Colors
- **White** (`#FFFFFF`): Primary interactive text on dark

### Neutral Scale
- **Text Primary** (`#FFFFFF`): Headings, body on black
- **Text Secondary** (`#888888`): Labels, metadata
- **Text Muted** (`#555555`): Inactive waveform, placeholders

### Surface & Borders
- **Background** (`#000000`): App background
- **Surface** (`#1A1A1A`): Cards, studio panels
- **Surface Raised** (`#252525`): Input fields
- **Border** (`#2E2E2E`): Dividers

### Semantic / Status
- **Playing** (`#F5C542`): Active playback (uses brand gold)
- **Success** (`#22C55E`): Generation complete
- **Error** (`#EF4444`): Failed generation
- **Processing** (`#60A5FA`): In-progress generation

## 3. Typography Rules

### Font Family
Primary: Inter, fallback: system-ui, sans-serif

### Hierarchy
| Role | Font | Size | Weight | Line Height | Letter Spacing | Notes |
|------|------|------|--------|-------------|----------------|-------|
| Display | Inter | 52px | 700 | 1.1 | -0.02em | Marketing hero |
| H1 | Inter | 36px | 700 | 1.2 | -0.01em | Page titles |
| H2 | Inter | 24px | 600 | 1.3 | 0 | Section headings |
| H3 | Inter | 18px | 600 | 1.4 | 0 | Card titles |
| Body | Inter | 16px | 400 | 1.6 | 0 | Prose content |
| Label | Inter | 13px | 500 | 1.4 | 0.02em | Studio labels |
| Caption | Inter | 12px | 400 | 1.4 | 0 | Timestamps |

### Principles
- Never use gold for body text — insufficient contrast on black
- Labels in the studio use 13px/500 with slight letter-spacing for precision

## 4. Component Stylings

### Buttons
- **Primary**: bg `#F5C542`, text `#000000`, padding `10px 20px`, radius `6px`, font 15px/600
- **Secondary**: bg `transparent`, border `1px solid #2E2E2E`, text `#FFFFFF`
- **Ghost**: bg `transparent`, text `#888888`, hover text `#FFFFFF`

### Cards & Containers
- bg `#1A1A1A`, border `1px solid #2E2E2E`, radius `8px`, padding `20px`
- Voice cards: waveform thumbnail, play button overlay on hover

### Inputs & Forms
- bg `#252525`, border `1px solid #2E2E2E`, radius `6px`, padding `10px 14px`, text `#FFFFFF`
- Focus: border `#F5C542`

### Navigation
- Left sidebar `#000000`, 240px
- Top nav `#000000`, 56px, border-bottom `#1A1A1A`

## 5. Layout Principles

### Spacing System
- **4px** — Waveform track padding
- **8px** — Studio control spacing
- **12px** — Timeline segment gaps
- **16px** — Card padding
- **20px** — Panel spacing
- **24px** — Section gaps
- **32px** — Feature blocks
- **48px** — Page sections

### Grid & Container
- Max width 1200px. Studio layout: sidebar 240px + fluid editor.

### Whitespace Philosophy
Minimalism is the product identity — every element must justify its existence.

### Border Radius Scale
- **None** (0px): Waveform timeline, studio track
- **Sm** (4px): Playback badges, tags
- **Md** (6px): Buttons, inputs
- **Lg** (8px): Voice cards
- **Full** (9999px): Play/stop buttons, avatars

## 6. Depth & Elevation

| Level | Treatment | Use |
|-------|-----------|-----|
| Flat | `none` | Studio canvas |
| Raised | `0 0 0 1px rgba(255,255,255,0.06)` | Cards |
| Overlay | `0 4px 20px rgba(0,0,0,0.8)` | Dropdowns |
| Modal | `0 8px 40px rgba(0,0,0,0.9)` | Dialogs |

## 7. Do's and Don'ts

### Do
- Use waveform visualisations for all audio content
- Use gold to signal active/playing state
- Keep the interface minimal — audio is the star

### Don't
- Don't use gold as body text color — fails contrast on black
- Don't introduce additional brand colors
- Don't animate anything other than audio playback elements

## 8. Responsive Behavior

### Breakpoints
| Name | Width | Key Changes |
|------|-------|-------------|
| Mobile | 0–767px | Single column, simplified studio |
| Tablet | 768–1023px | 2-column, collapsed sidebar |
| Desktop | 1024px+ | Full studio layout |

### Touch Targets
Minimum 44×44px. Play/pause button is 48×48px minimum.

### Collapsing Strategy
Studio timeline stacks vertically on mobile. Sidebar collapses to icon strip.

## 9. Agent Prompt Guide

### Quick Color Reference
- Active/Playing: Gold (`#F5C542`) — text is black (`#000000`) on gold
- Background: Pure black (`#000000`)
- Surface: Dark gray (`#1A1A1A`)
- Text: White (`#FFFFFF`)
- Border: `#2E2E2E`
- Processing: Blue (`#60A5FA`)

### Iteration Guide
1. Gold CTA text must be black — the gold is too bright for white text
2. Never add a third color to the brand palette
3. Waveforms use `#555555` inactive and `#F5C542` for the played portion
4. Play buttons are always circular — full border-radius
5. Studio controls are 36–40px with clear affordances