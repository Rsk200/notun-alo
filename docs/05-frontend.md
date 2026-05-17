# Frontend Architecture — Notun Alo

> **Document:** `docs/05-frontend.md`  
> **Version:** 1.0  
> **Last Updated:** May 2026

---

## Table of Contents

1. [Page Inventory](#1-page-inventory)
2. [CSS Architecture](#2-css-architecture)
3. [JavaScript Modules](#3-javascript-modules)
4. [Responsive Behavior](#4-responsive-behavior)
5. [State Management](#5-state-management)
6. [Error Handling](#6-error-handling)
7. [Dark Mode](#7-dark-mode)
8. [Bilingual System](#8-bilingual-system)

---

## 1. Page Inventory

### 1.1 `index.php` — Landing Page (622 lines)

The public-facing landing page. Redirects authenticated users to their role-specific dashboard.

**Sections:**

| Section | Description |
|---|---|
| **Hero** | Full-viewport hero with animated stats counter, impact ticker marquee showing live platform totals (kg recycled, CO₂ saved), bilingual badge |
| **How It Works** | 3-step grid (Sign Up → Schedule → Earn) with glassmorphism cards and animated step number overlays |
| **Stats Strip** | Horizontal bar showing platform-wide metrics: kg recycled, active users, points rewarded, CO₂ prevented; numbers animate on scroll via IntersectionObserver |
| **Shop Preview** | Product grid (2-4 columns) with client-side pagination; shows name, points + cash price, stock badge; links to `shop.php` |
| **Testimonials** | Carousel of user testimonials with avatar, quote, name; auto-rotates every 5 seconds |
| **About Section** | Brief mission statement with "Learn More" link to `about.php` |
| **CTA Banner** | Full-width call-to-action "Start Recycling Today" with gradient background and register button |
| **Footer** | Site links, social icons, copyright, dark mode toggle, language toggle |
| **Navbar** | Sticky top navigation with brand logo, nav links (Home, About, Login, Register), dark mode toggle, bilingual toggle |

**Key features:**
- Client-side pagination on shop preview using vanilla JS
- Dark mode CSS variable toggling
- Bilingual text via inline `$t()` closure
- `isDatabaseInitialized()` check with redirect to `init_db.php` if DB is empty
- Product seed check — if no products exist, seed data is inserted

### 1.2 `dashboard.php` — User Dashboard (561 lines)

The primary authenticated landing page for users with role = `'user'`.

**Sections:**

| Section | Description |
|---|---|
| **Hero Greeting** | Personalized welcome with user name, rotating eco-themed quotes (JS rotation), dark mode-aware gradient card |
| **Tier Card** | Gamified rank display: Bronze / Silver / Gold / Platinum with animated circular progress bar (CSS `conic-gradient`), current points, points to next tier |
| **Stat Cards (3)** | Reward Points (with pending pickup count), Completed Pickups (with scheduled count), Total Recycled (with CO₂ equivalent) — each with icon, value, subtitle hint |
| **CTA Banner** | "Schedule a Pickup" link + "Visit Shop" link with hover effects |
| **Activity Timeline** | Recent 5 pickups with status badges (Pending/Assigned/Completed), category icons, dates — empty state message if no activity |
| **Leaderboard** | Top 10 recyclers ranked by `lifetime_points` with crown medal for #1, silver for #2, bronze for #3 |
| **About Strip** | Compact "About Notun Alo" section with stats and team mention |
| **Mobile Nav** | Scroll-hide behavior: navbar hides on scroll down, shows on scroll up; bottom mobile nav with icon tabs |

**Key features:**
- Mobile-first responsive layout with two breakpoints (768px, 480px)
- Animated point counter using `requestAnimationFrame`
- Profile picture fallback to initial-letter avatar
- Tier calculation logic: Bronze (<500), Silver (500-1499), Gold (1500-4999), Platinum (>=5000)
- CO₂ calculation: `total_kg_recycled * 1.2`
- Rotating quotes array changes hero greeting subtitle

### 1.3 `shop.php` — Upcycle Shop (244 lines)

Product marketplace where users can browse and purchase upcycled goods with points + cash.

**Features:**

| Feature | Description |
|---|---|
| **Auto-Seed** | If `products` table is empty, inserts 5 default products (Notebook, Tote Bag, Pen Set, Planter Pot, Coaster Set) |
| **Product Grid** | Cards showing product image (400px Unsplash), name, description, points + cash price, stock badge |
| **Search** | Real-time client-side search by product name using `input` event listener |
| **Category Filter** | Dropdown filter by category (Stationery, Accessories, Home) |
| **Pagination** | 12 products per page with numbered page buttons, prev/next controls |
| **Points Display** | Shows user's current points balance in header |
| **Flash Messages** | Success/error alerts for purchase attempts |

**States:**
- **Empty:** No products matching filter/search — "No products found" with illustration
- **Out of stock:** Product card shows `"Out of stock"` badge and disabled purchase button
- **Insufficient points:** Visual indicator when user cannot afford a product

### 1.4 `chatbot.php` — AI Assistant (499 lines)

Full-page chatbot interface with sidebar redesign using Tabler Icons.

**Layout:**

```
┌─────────────────────────────────────────────────────────────┐
│  Sidebar (320px)  │            Main Chat Area               │
├────────────────────┤────────────────────────────────────────┤
│ Chat History       │  Header (user info, mode selector)     │
│ • General mode     │  Message Bubbles (scrollable)          │
│ • Points mode      │  • User (right-aligned, green)         │
│ • Schedule mode    │  • AI (left-aligned, card)             │
│                    │  Suggestion Chips                      │
│ New Chat button    │  Input bar (auto-expand textarea)      │
└────────────────────┴────────────────────────────────────────┘
```

**Features:**

| Feature | Description |
|---|---|
| **3 Conversation Modes** | General (default), Points (points-focused), Schedule (pickup scheduling) — each with custom greeting |
| **Empty State** | Suggestion cards: "Check Points", "Recycling Guide", "Schedule Pickup", "Impact Stats" |
| **Message Bubbles** | User messages (right, green bg), AI replies (left, white card with shadow), typing indicator (animated dots) |
| **Typing Indicator** | 3 bouncing dots animation while waiting for API response |
| **Auto-Expanding Textarea** | Grows up to 120px height as user types, Shift+Enter for newline, Enter to send |
| **Client-Side History** | `localStorage` per user/session — persists conversations across page reloads |
| **Dark Mode** | Full dark theme with dedicated CSS variables for sidebar, chat area, bubbles, inputs |
| **Tabler Icons** | Icons for sidebar items, send button, mode toggle, history items |
| **Suggestion Chips** | Context-aware clickable suggestions that populate the input |
| **Scroll-to-Bottom** | Auto-scrolls on new message |

**States:**
- **Loading:** Typing indicator dots with "thinking" animation
- **Error:** Red error bubble with retry suggestion chip
- **Empty (no history):** Welcome message with suggestion cards
- **Scheduling flow:** Step-by-step category → weight → date → confirmation

### 1.5 `about.php` — About Page (315 lines)

Public page explaining the platform's mission and team.

**Sections:**

| Section | Content |
|---|---|
| **Hero** | Gradient card with animated background circles, title "Trash to Treasure" / "বর্জ্য থেকে সম্পদ", subtitle about ULAB Buildfest 2026 |
| **4 Pillar Cards** | Mission, Vision, Motivation, Promise — each with icon, title, bilingual description, glassmorphism card |
| **Team Section** | "The GhostRiders" team grid with member cards (name, role), ULAB Buildfest 2026 mention, build timeline |
| **CTA** | "Join Us" banner linking to register page |
| **Footer** | Same as landing page (dark mode toggle, language toggle, copyright) |

### 1.6 `login.php` — Login Page (167 lines)

Split-layout authentication page.

**Layout:**
- **Left panel (brand):** Gradient background with Notun Alo branding, platform stats (kg recycled, users, points), tagline
- **Right panel (form):** Clean white card with email input, password input (with visibility toggle), submit button, register link

**Features:**
- Password visibility toggle (eye icon)
- Server-side validation with bilingual error messages
- `password_verify()` authentication
- Role-based redirect (admin → `admin.php`, agency → `agency.php`, user → `dashboard.php`)
- Real platform stats shown on the brand panel
- Dark mode support

### 1.7 `register.php` — Registration Page (194 lines)

Split-layout registration similar to login.

**Left panel features:**
- Benefit list with icons (Earn Points, Schedule Pickups, Track Impact, Shop Rewards)
- Stats display

**Right panel form:**
| Field | Validation |
|---|---|
| Name (required) | Non-empty |
| Email (required) | `filter_var(FILTER_VALIDATE_EMAIL)` |
| Phone | Optional, input pattern |
| Address (required) | Non-empty |
| Password (required) | Min 6 characters |
| Confirm Password | Must match password |

**Flow:**
1. Validate inputs
2. Check for duplicate email
3. `password_hash(PASSWORD_BCRYPT)`
4. Transactional insert into `users` + `rewards` (0 points)
5. Redirect to login with flash message

**States:**
- **Validation errors:** Inline error messages below each field
- **Duplicate email:** "An account with this email already exists."
- **Success:** Flash message + redirect to login

### 1.8 `admin.php` — Admin Dashboard (230 lines)

Admin-only dashboard with platform overview.

**Sections:**

| Section | Description |
|---|---|
| **Stats Row** | 4 stat cards: Total Waste (kg), Total Pickups, Registered Users, Products in Shop |
| **YC-Style Docs Card** | Link to `admin_docs.php` styled like Y Combinator documentation |
| **Global Leaderboard** | Top 10 recyclers with medal emojis (🥇🥈🥉), user avatars/initials, points, email |
| **Email Search** | Admin can search users by email with autocomplete-style results |
| **Churn Risk Link** | Link to `admin_churn_month.php` for ML-based churn monitoring |
| **Agency List** | Dropdown of available agencies for assignment |

**Features:**
- Uses `requireRole('admin')` guard
- `leaderboard.css` for staggered animate-in of leaderboard rows
- `sortable-table.css` + `sortable-table.js` for interactive table sorting
- Stats formatted with `en2bn()` for Bengali numeral display

---

## 2. CSS Architecture

### 2.1 `style.css` — Main Stylesheet (3,269 lines)

The complete design system for the platform.

**CSS Variables (`:root` and `.dark-mode`):**

| Category | Variables |
|---|---|
| **Brand** | `--brand-dark: #0A2E1E`, `--brand-primary: #1D9E75`, `--brand-light: #E6F5EE` |
| **Text** | `--text-primary`, `--text-secondary`, `--text-muted` |
| **Background** | `--bg-page: #F5F7F2`, `--bg-card: #FFFFFF` |
| **Borders** | `--border: #E5E7EB` |
| **Auth** | `--auth-bg-left: linear-gradient(135deg, #064e3b, #065f46, #1D9E75)` |

**Dark Mode** — All variables redeclared under `body.dark-mode`:
- Brand accent shifts to `#34d399`
- Backgrounds invert to near-black (`#061405`, `#0d1a0e`)
- Text becomes light (`#E2E8F0`, `#94A3B8`)
- Borders become dark green (`#1e3222`, `#1f2e24`)

**Key CSS patterns:**

| Pattern | Usage |
|---|---|
| **Glassmorphism** | `background: rgba(...)`, `backdrop-filter: blur(12px)`, `border: 1px solid rgba(...)` — used on modals, cards, navbars |
| **Animations** | `@keyframes fadeUp`, `@keyframes slideIn`, `@keyframes pulse` — scroll reveal, page transitions, loading states |
| **Preloader** | Full-screen overlay with spinning leaf animation, auto-hides after 1.5s via JS |
| **Toast Notifications** | Fixed bottom-right container, slide-in animation, progress bar, auto-dismiss after 4s |
| **Scroll Reveal** | Elements with `[data-reveal]` start as `reveal-hidden` (opacity 0, translateY 20px), animate in via `reveal--visible` |
| **Page Transitions** | Fade-in on load, fade-out on link click via `page-transition-enter` / `page-transition-exit` classes |
| **Stat Counters** | `.stat-counter` elements animate numeric values on scroll into view |
| **Tier Progress** | Circular progress via `conic-gradient()` with dynamic percentage |
| **Auth Layout** | Split-panel (40% brand / 60% form) with responsive single-column at 768px |

**Responsive breakpoints:**
- **768px** — Tablet: grid collapses to 2 columns, auth becomes single column, sidebar hidden
- **640px** — Small tablet: font scaling, tighter paddings
- **480px** — Mobile: single column, full-width cards, stacked nav

### 2.2 `docs.css` — Documentation Page Styling (138 lines)

Used by `admin_docs.php` and `docs.php`.

- Glassmorphism sidebar with sticky positioning
- Status badges (active, deprecated, new)
- Code block styling with syntax highlight baseline
- Responsive documentation layout

### 2.3 `leaderboard.css` — Premium Leaderboard (411 lines)

Used on `admin.php` and `dashboard.php` leaderboard sections.

| Feature | Description |
|---|---|
| **Staggered Animations** | Each row animates in with sequential delay via `nth-child()` |
| **Top-3 Gradients** | Gold/silver/bronze gradient backgrounds for podium positions |
| **Avatar Rings** | Circular avatar with green ring (active) or gray (inactive) |
| **"You" Highlight** | Current user's row highlighted with brand green background |
| **Row Hover** | Subtle lift + shadow effect on hover |

### 2.4 `sortable-table.css` — Sortable Tables (166 lines)

Used by admin pages with `.data-table[data-sortable]`.

- Caret indicators (▲/▼) on sortable column headers
- Hover highlight on sortable columns
- Active sort column background tint
- Dark mode compatible
- Search bar styling within table header

---

## 3. JavaScript Modules

### 3.1 `animations.js` — Global Animation System (169 lines)

Loaded on every page.

| Feature | Lines | Description |
|---|---|---|
| **Preloader** | 11-16 | Hides `#preloader` after 1.5s timeout |
| **Scroll Reveal** | 19-37 | `IntersectionObserver` (threshold 0.12) adds `reveal--visible` to `[data-reveal]` elements with staggered delay |
| **Counter Animation** | 40-81 | `IntersectionObserver` (threshold 0.5) animates `.stat-counter` values from 0 → target using `requestAnimationFrame` with `easeOutCubic` |
| **Navbar Scroll** | 84-94 | Adds `navbar--scrolled` class when `scrollY > 50`, removes when above |
| **Page Transitions** | 97-120 | Intercepts `<a>` clicks (internal only), applies exit animation, navigates after 250ms |
| **Toast System** | 127-164 | `window.showToast(message, type)` — creates toast container dynamically, shows notification with progress bar, auto-dismisses after 4s |

### 3.2 `sortable-table.js` — Client-Side Table Sorting (74 lines)

Lightweight, dependency-free sortable tables.

- Attaches to `table.data-table[data-sortable]`
- Skips column #0 (row numbers), columns with `data-no-sort`, and action columns
- Bengali digit support: converts ০-৯ to ASCII before numeric comparison
- Mixed-type sorting: numbers sort numerically, strings alphabetically
- Alternating asc/desc on each click
- Highlights sorted column cells with `.sorted-col`

### 3.3 `dashboard_chart.js` — Chart.js Stacked Bar (23 lines)

Used on `user_impact.php` and `admin_impact.php`.

```javascript
function renderNotunAloStackedImpactChart(canvasId, rows)
```

- Creates a Chart.js stacked bar chart
- X-axis: months (last 12)
- Y-axis: CO₂ saved (kg)
- Color mapping by category (Paper=green, Plastic=blue, Metal=brown, E-waste=orange, etc.)
- Responsive with aspect ratio preservation

---

## 4. Responsive Behavior

**Strategy:** Mobile-first with progressive enhancement.

**Breakpoints:**

| Breakpoint | Target | Changes |
|---|---|---|
| `768px` | Tablet | Auth → single column, 2-column grids, sidebar hidden, navbar collapses |
| `640px` | Small tablet | Font-size scaling (h1: 1.8rem, body: 0.9rem), tighter padding (16px gutters) |
| `480px` | Phone | Single column everywhere, stacked nav, full-width cards, hidden non-essential elements |

**Specific behavior:**

| Component | Desktop | Mobile (≤768px) |
|---|---|---|
| **Navbar** | Sticky full-width with links | Hamburger menu or scroll-hide |
| **Product Grid** | 3-4 columns | 1-2 columns |
| **Leaderboard** | Full table | Card view per row |
| **Chatbot** | Sidebar (320px) + chat | Sidebar hidden, toggleable |
| **Auth Pages** | Split layout (40/60) | Single stacked column |
| **Stats Strip** | Horizontal row | Vertical stacked |
| **Footer** | 3-column grid | Single column |

---

## 5. State Management

| Layer | Mechanism | Scope |
|---|---|---|
| **Backend** | PHP `$_SESSION` | Authentication, user ID, role, language preference, flash messages |
| **Client-side (chat)** | `localStorage` keyed by user+session | Chat conversation history |
| **Client-side (DOM)** | CSS classes, `data-*` attributes | Dark mode, scroll states, reveal animations, active sort columns |
| **Client-side (filters)** | In-memory JS arrays + DOM re-render | Shop search, category filter, pagination |

---

## 6. Error Handling

| Scenario | Handling |
|---|---|
| **Database unreachable** | `config.php` catches `PDOException`, returns JSON error for API, dies with message |
| **Session expired** | `requireLoginJson()` returns 401 JSON, `requireLogin()` redirects to `login.php` |
| **Chatbot API error** | `catch(Throwable)` returns friendly error in detected language + null action |
| **RAG service down** | Circuit breaker (3 failures → 5-min cooldown), falls back to Pollinations.ai → rule-based fallback |
| **Empty tables** | `isDatabaseInitialized()` checks `products` table, redirects to `init_db.php` if missing |
| **Validation errors** | Inline messages on forms, `setFlash()` + `getFlash()` for post-redirect messages |
| **Network failure** | Graceful degradation — static data shown when API unavailable (dashboard stats from SQL) |

**Toast notification types:**
- `success` — Green checkmark, operation confirmed
- `error` — Red X, failure or exception
- `info` — Blue info icon, general notification

---

## 7. Dark Mode

**Implementation:**
- CSS custom properties (variables) on `:root` (light) and `body.dark-mode` (dark)
- JavaScript toggle saves preference to `localStorage` as `'darkMode'`
- On page load, JS checks `localStorage` and applies `.dark-mode` class to `<body>`
- Toggle button in navbar and footer on all pages
- All pages include dedicated dark mode style blocks for page-specific elements

**Toggle logic (pseudocode):**
```javascript
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
}
toggleButton.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
});
```

---

## 8. Bilingual System

**Architecture:**
- `includes/lang.php` loads at start via `config.php`
- Dictionary of `$translations['en']` and `$translations['bn']` with ~185 keys each
- Language set via `$_SESSION['lang']` (default `'en'`)
- Toggle via `?lang=bn` or `?lang=en` query parameter — redirects back without param
- `en2bn($number)` converts ASCII digits to Bengali numerals (e.g., `123` → `১২৩`)
- `$t('English', 'বাংলা')` inline function for simple strings
- All UI text, errors, hints, status labels are translated

**Coverage:**
Every user-facing string is bilingual: navbar, dashboard labels, shop text, admin panel, chatbot messages, validation errors, flash messages, impact descriptions, leaderboard labels.

**Numeral conversion:**
- Admin stats, user points, weight values, dates — all run through `en2bn()` when `lang === 'bn'`
- Chatbot API detects Bengali/Banglish via Unicode range and keyword matching
- `sortable-table.js` converts Bengali digits for numeric column sorting
