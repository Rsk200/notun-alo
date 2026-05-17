# Authentication & Security

> **Notun Alo (নতুন আলো)** — Smart Recycling Platform  
> Document version: 1.0 | Last updated: May 2026

---

## 1. Authentication Flow

### 1.1 Registration (`register.php`)

The registration process creates user accounts with validated input, hashed passwords, and initialised reward records.

**Form Fields:**
- **Name** (required) — full name of the user
- **Email** (required) — validated with `filter_var(FILTER_VALIDATE_EMAIL)`
- **Phone** (optional) — contact number
- **Address** (required) — full address for pickup scheduling
- **Password** (required, min 6 characters) — validated with `strlen($pass) < 6`
- **Confirm Password** (required) — must match `password`

**Processing Logic:**
1. Server-side validation checks all required fields, email format, password length (≥6), and password match
2. Checks for duplicate email: `SELECT id FROM users WHERE email = ?`
3. Begins a **transactional insert** (`$pdo->beginTransaction()`):
   - Inserts user into `users` table with role `'user'` and `PASSWORD_BCRYPT` hash
   - Inserts initial reward record into `rewards` table with `total_points = 0`, using `lastInsertId()`
4. Commits the transaction on success, rolls back on failure
5. Sets a flash message and redirects to `login.php`

**Code Reference:** `register.php:23-64`

### 1.2 Login (`login.php`)

Users authenticate via email + password with server-side credential verification.

**Processing Logic:**
1. Fetches user record: `SELECT * FROM users WHERE email = ? LIMIT 1`
2. Verifies password with `password_verify($password, $user['password'])`
3. On success, sets session variables:
   - `$_SESSION['user_id']` — user's primary key
   - `$_SESSION['name']` — user's full name
   - `$_SESSION['email']` — user's email address
   - `$_SESSION['role']` — user's role (`user`, `admin`, or `agency`)
4. Role-based redirect:
   - `admin` → `admin.php`
   - `agency` → `agency.php`
   - `user` → `dashboard.php`
5. On failure, shows generic "Invalid email or password" message (no username enumeration)
6. Login page displays **platform stats** (total kg recycled, active users, points rewarded) with a split layout (brand panel + form panel)

**Code Reference:** `login.php:22-48`

### 1.3 Session Management

The platform uses **PHP native sessions** stored server-side.

- **Start Session:** `startSession()` function checks `session_status() === PHP_SESSION_NONE` before calling `session_start()` (config.php:111-115)
- **Storage:** Default PHP file-based session storage (server-side, not in cookies)
- **Session Variables:**
  - `user_id` — authenticated user identifier
  - `name` — display name for personalisation
  - `role` — authorisation level
  - `email` — user email
  - `lang` — language preference (`en` or `bn`)
  - `flash` — one-time notification messages
- **Language Persistence:** Language selection (`$_GET['lang']`) is stored in the session and persists across pages (lang.php:12-18)

### 1.4 Logout (`logout.php`)

- Calls `session_destroy()` to destroy all session data server-side
- Clears client-side localStorage entries prefixed with `notun_alo_chat_history_user_` (all chat history per user)
- Displays a logout animation screen with a `<meta http-equiv="refresh" content="1;url=index.php">` redirect after 1 second
- JavaScript fallback redirect via `window.location.replace('index.php')` after 1200ms

**Code Reference:** `logout.php`

### 1.5 Role-Based Authorization

The platform implements three levels of access control:

| Function | Description | Redirect On Failure |
|---|---|---|
| `isLoggedIn()` | Checks `$_SESSION['user_id']` is set | — |
| `requireLogin()` | Redirects to `login.php` if not logged in | `login.php` |
| `requireLoginJson()` | Returns 401 JSON response for API endpoints | `{"reply": "Please log in again.", "action": null}` |
| `requireRole('admin')` | Requires both login AND specific role | `dashboard.php` |

**Code Reference:** `config.php:128-165`

---

## 2. Protected Routes

| Route | Guard | Notes |
|---|---|---|
| `dashboard.php` | `requireLogin()` | User dashboard |
| `shop.php` | None explicit (graceful fallback for guests) | Shows products for unauthenticated users, login for purchase |
| `chatbot.php` | `requireLogin()` | AI assistant interface |
| `chatbot_api.php` | `requireLoginJson()` | AJAX chatbot endpoint |
| `purchase.php` | `requireLogin()` | Product purchase |
| `admin.php` | `requireRole('admin')` | Checks role is `admin` (allows `super_admin`) |
| `admin/*` | `requireRole('admin')` | All files in admin/ directory |

**Admin role check in `requireRole()`:**
```php
function requireRole(string $role): void {
    requireLogin();
    startSession();
    if ($_SESSION['role'] !== $role) {
        redirect('dashboard.php');
    }
}
```

---

## 3. Security Practices

### 3.1 SQL Injection Prevention

All database queries use **PDO prepared statements** with parameterized queries. No string interpolation is used in SQL.

```php
// Safe — parameterized query
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);

// Unsafe — NEVER used in this codebase
// $pdo->query("SELECT * FROM users WHERE email = '$email'");  // ❌
```

Additional protections:
- `PDO::ATTR_EMULATE_PREPARES => false` — uses real MySQL prepared statements (config.php:41)
- All `INSERT`, `UPDATE`, `SELECT`, and `DELETE` queries use parameterized values

### 3.2 XSS (Cross-Site Scripting) Prevention

All user data output uses the `e()` helper function:

```php
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
```

- Applied to ALL user-generated content rendered in HTML: names, emails, addresses, messages, search terms
- Also applied in system prompts: `htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8')` (chatbot_context.php:8)
- JSON responses use `JSON_UNESCAPED_UNICODE` flag but avoid raw HTML injection

### 3.3 Password Storage

- **Algorithm:** `password_hash($password, PASSWORD_BCRYPT)` — uses bcrypt (default algorithm)
- **Minimum length:** 6 characters (enforced at registration)
- **Verification:** `password_verify($password, $user['password'])` — timing-safe comparison
- No plaintext passwords are ever stored or logged

### 3.4 Session Security

- Session variables are checked **server-side** for every protected request
- No sensitive data (passwords, PII) is stored in client-side cookies
- Session ID is managed by PHP's native session handler
- `session_destroy()` on logout clears server-side data
- No session fixation protection implemented (potential improvement area)

### 3.5 API Security

- **CORS:** Flask RAG service includes CORS headers for cross-origin requests
- **Authentication:** `requireLoginJson()` on all AJAX endpoints returns 401 JSON for unauthenticated requests
- **No public write endpoints:** All state-changing operations require authentication
- **Input validation:** JSON body decoded from `php://input` with type checking
- **Error responses:** Generic messages, no stack traces exposed

### 3.6 Environment Variables

- Sensitive configuration stored in `.env` file (excluded from Git via `.gitignore`)
- `.env.example` provided as a template with placeholder values
- `loadEnv()` function reads `.env` at runtime, populates `$_ENV` and `$_SERVER`
- Environment variables used for: DB credentials, API keys, service URLs, SSL config

### 3.7 Error Handling

- All database operations wrapped in `try/catch` blocks
- Errors logged via `error_log()` — never displayed to users
- Generic error messages returned (e.g., "Registration failed. Please try again.")
- PDO uses `ERRMODE_EXCEPTION` for consistent exception-based error handling
- Catch blocks in `chatbot_api.php` log errors and return friendly messages

### 3.8 Database Security

- **Separate MySQL user** for application (limited to database privileges)
- **SSL support:** `PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT` enabled for remote/Aiven connections
- **utf8mb4 encoding:** Prevents charset-based attacks and supports full Bengali/Unicode
- **Table charset:** All tables use `utf8mb4_unicode_ci` collation

### 3.9 AI Endpoint Protection

- **RAG service** (Flask) listens only on `localhost:5000` by default — not exposed publicly
- **Pollinations.ai** external API used via outbound HTTPS calls only
- **Circuit breaker** auto-opens after 3 consecutive failures to protect against resource exhaustion
- **No user data** sent to Pollinations.ai in system prompts (contextual only)

---

## 4. Initial Setup & Verification

### Database Initialisation

`init_db.php` automatically runs on first load if database tables don't exist. It:
1. Checks if the `users` table exists via `SELECT 1 FROM users LIMIT 1`
2. If not, executes the full SQL schema from `clean_merged_notun_alo.sql`
3. If tables exist, applies incremental fixes (AUTO_INCREMENT, constraints, collation)
4. Adds UNIQUE constraint on `users.email` to prevent duplicate accounts

**Code Reference:** `init_db.php`
