# 🎲 Adventures of the Dice

**Snakes & Ladders — Full PHP Web Game**
CSC 4370/6370 · Spring 2026 · Georgia State University

---

## 🌐 Live Links

| Resource | URL |
|----------|-----|
| **CODD Live URL** | https://codd.cs.gsu.edu/~dnagarajan1/WP/PW/Dhanush_Adventures_0f_the_Dice/ |
| **GitHub Repository** | https://github.com/Dhanush-0017/Dhanush_Adventures_0f_the_Dice |

---

## 👥 Team

| Name | Student ID | Email | Role | Scrum Role |
|------|-----------|-------|------|-----------|
| Dhanush Nagarajan | 002932859 | dnagarajan1@student.gsu.edu | GitHub Owner, Auth, Sessions, Leaderboard, CODD Deploy | Scrum Master |
| M. Abhinaya | 002983679 | gmandela1@student.gsu.edu | Game Board, Dice Logic, Snakes/Ladders, CSS Animations | Project Owner |

---

## 📖 Project Description

Adventures of the Dice is a fully server-driven 2-player PHP web implementation of Snakes & Ladders. Players register or log in, select a difficulty level, then alternate turns by submitting dice rolls via POST forms.

**How it works:**
- PHP's `rand(1,6)` computes each move server-side
- New position is checked against PHP associative arrays mapping snake heads to tails and ladder bases to tops
- `$_SESSION` persists both player positions, the active turn, and full dice roll history across every page reload
- The 100-cell board is generated entirely via a PHP loop
- Bonus tiles trigger extra-roll, skip-a-turn, and mystery-boost events
- First player to reach or exceed cell 100 wins and is redirected to the win/leaderboard page

**No JavaScript. No database. Pure PHP + HTML5 + CSS3.**

---

## 🎮 Features

### Core Game
- 100-cell board generated dynamically by a PHP loop
- 2-player mode (take turns on same device) or vs AI opponent
- 3 difficulty levels: Beginner (3 snakes/3 ladders), Standard (6/5), Expert (9/4)
- Dice roll via POST form — server-side `rand(1,6)`
- Snake heads slide player down, ladder bases climb player up
- Bonus tiles: extra roll, skip opponent's turn, mystery boost
- Win condition: first to reach or exceed cell 100

### Sessions & Cookies
- `$_SESSION['positions'][]` — both player positions persist across page reloads
- `$_SESSION['current_turn']` — tracks whose turn it is
- `$_SESSION['dice_history'][]` — full dice roll history
- `$_SESSION['scores'][]` — score tracking for leaderboard
- Cookie-based leaderboard persistence (30 days) across visits

### AI Component
- 3 AI strategies: Easy (random), Medium (greedy snake avoidance), Hard (two-turn lookahead)
- AI Narrator text box after every roll with story-style messages
- Event cells trigger dynamic events: bonus, penalty, skip, warp
- `$_SESSION['events_log'][]` tracks every event for the Adventure Recap
- Move Probability Map — shows each possible landing cell with percentage
- Path Analysis — compares human vs AI vs optimal route at game end

### Authentication
- Register with username + password (stored via `password_hash()` in `data/users.txt`)
- Login validates credentials with `password_verify()`
- All game pages protected by session guard — redirects to login if not authenticated
- Logout calls `session_destroy()` cleanly

### Responsive Design
- Mobile (360px), tablet (768px), and desktop (1200px+) breakpoints
- CSS Grid for board layout, CSS Flexbox for page layout
- CSS keyframe animations for snake slide, ladder climb, warp, bonus pulse
- No external CSS frameworks — pure CSS3

---

## 📁 Folder Structure

```
Dhanush_Adventures_0f_the_Dice/
├── css/
│   ├── style.css          # Global styles
│   ├── auth.css           # Login/register/dashboard styles
│   ├── board.css          # Board grid, tokens, animations
│   └── responsive.css     # Mobile/tablet/desktop breakpoints
├── data/
│   └── users.txt          # Flat file user storage (no database)
├── includes/
│   ├── header.php         # Common header + nav
│   ├── footer.php         # Common footer
│   ├── session_check.php  # Auth guard
│   ├── functions.php      # Reusable PHP helper functions
│   ├── board_config.php   # Snake/ladder arrays + event cells
│   └── ai_functions.php   # AI strategies + narrator engine
├── index.php              # Landing page (redirects to login or dashboard)
├── register.php           # User registration
├── login.php              # User login
├── logout.php             # Session destroy + redirect
├── dashboard.php          # Game setup (difficulty, mode, AI strategy)
├── game.php               # Main game board (100-cell)
├── process_roll.php       # POST handler: dice roll + move logic
├── ai_turn.php            # AI turn handler
├── win.php                # Win/lose screen + adventure recap
├── leaderboard.php        # Top 10 scores display
├── team.html              # Team intro page
├── Sprint_log.html        # Scrum sprint documentation
├── dev_journal.html       # Daily development journal
└── README.md              # This file
```

---

## 🚀 Setup Instructions

### Run Locally (XAMPP / WAMP / MAMP)

1. Clone the repository:
   ```
   git clone https://github.com/Dhanush-0017/Dhanush_Adventures_0f_the_Dice.git
   ```

2. Move the folder into your web server's root directory:
   - XAMPP: `C:/xampp/htdocs/`
   - WAMP: `C:/wamp64/www/`
   - MAMP: `/Applications/MAMP/htdocs/`

3. Ensure the `data/` folder is writable:
   ```
   chmod 755 data/
   chmod 666 data/users.txt
   ```
   *(On Windows, ensure the folder is not read-only)*

4. Start Apache in XAMPP/WAMP/MAMP.

5. Open in browser:
   ```
   http://localhost/Dhanush_Adventures_0f_the_Dice/
   ```

6. Register a new account and start playing.

### Requirements
- PHP 7.4 or higher
- Apache web server (or any PHP-capable server)
- No database required
- No external libraries or Composer packages

---

## 🔐 Security Notes

- All POST inputs sanitized with `htmlspecialchars()` and `filter_input()`
- Passwords stored using `password_hash()` (bcrypt) — never plain text
- All game pages protected by session guard (`includes/session_check.php`)
- No SQL — no risk of SQL injection (flat file storage only)
- No JavaScript — no XSS risk from dynamic DOM manipulation

---

## 🤖 AI Usage Disclosure

**This project used AI assistance. Full disclosure as required by the course:**

| AI Tool | What It Was Used For | What Was Written Independently |
|---------|---------------------|-------------------------------|
| Claude (Anthropic) | Assisted with structuring PHP logic for the AI narrator engine and move probability map | All core game logic (dice roll, snake/ladder lookup, session management, form handling) was written independently by team members |
| Claude (Anthropic) | Assisted with generating documentation files (Sprint_log.html, dev_journal.html, README.md, team.html) | All PHP, game flow, and CSS were designed and implemented by team members |

**All PHP code, game logic, session management, CSS, and HTML structure were implemented by Dhanush Nagarajan and M. Abhinaya.** AI tools were used for documentation assistance and code structure suggestions — not for copying complete functional code solutions.

---

## 📋 Grading Alignment

| Requirement | Implementation |
|-------------|---------------|
| Sessions | `session_start()` on every page, `$_SESSION` for all game state |
| Cookies | Leaderboard persisted via `setcookie()` for 30 days |
| Form Processing | All forms use POST, `filter_input()`, `htmlspecialchars()` |
| Login/Register | `password_hash()`, `password_verify()`, flat file storage |
| Session Guard | `includes/session_check.php` on all protected pages |
| PHP Game Logic | All logic server-side — `rand()`, arrays, conditionals, loops |
| No JavaScript | Zero `<script>` tags in entire project |
| No Database | No PDO, no MySQLi, no SQL anywhere |
| Responsive CSS | 360px / 480px / 768px / 1200px breakpoints in `responsive.css` |
| AI Narrator | `generateNarration()` in `ai_functions.php` |
| Adventure Recap | `$_SESSION['events_log'][]` displayed on `win.php` |
| Path Analysis | `generatePathAnalysis()` comparing human vs AI vs optimal |
| Probability Map | `rollProbabilities()` shown during game |

---

## 📅 Sprint Timeline

| Sprint | Dates | Focus |
|--------|-------|-------|
| Sprint 1 | Apr 7–9 | GitHub setup, CODD folder, README |
| Sprint 2 | Apr 10–12 | Login, register, session guards |
| Sprint 3 | Apr 13–15 | Game board, CSS, tokens |
| Sprint 4 | Apr 13–16 | Dice engine, AI, bonus tiles |
| Sprint 5 | Apr 16–18 | Win screen, leaderboard, animations |
| Sprint 6 | Apr 17–19 | Integration testing, final submission |

---

*Adventures of the Dice — CSC 4370/6370 Spring 2026 — Georgia State University*
