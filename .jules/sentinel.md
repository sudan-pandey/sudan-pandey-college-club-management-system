## 2026-08-15 - HTTP Security Headers in Include Header

**Vulnerability:** Missing HTTP security headers on rendered HTML pages allowed potential clickjacking (lack of X-Frame-Options), MIME-type sniffing (lack of X-Content-Type-Options), and excessive referrer information leakage.

**Learning:** PHP files sending output before executing header functions prevent standard HTTP response headers from being sent (`headers_sent()`). Reordering the PHP initialization block to the very top of `includes/header.php` before HTML output allows application-wide security headers to be safely attached.

**Prevention:** Always place PHP logic that sets response headers at the very top of layout include files before any HTML or whitespace output is sent to the client.
