=== Betting Bookings ===
Contributors: Finland93
Tags: betting, slips, tracker, bookkeeping, statistics
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track your personal betting slips (stake, rows, potential win, hits, outcome) and show summaries anywhere with shortcodes.

== Description ==

Betting Bookings is a simple admin tool for keeping a personal record of your betting slips. Add a slip with its stake, number of rows, potential payout and date; later mark it as a Win or Loss and record how many rows hit. The plugin keeps a running net profit/loss and exposes ready-made shortcodes for your pages.

This is a personal bookkeeping tool. It does not place bets, process payments, or connect to any bookmaker.

= Features =

* Add, edit and remove slips from a single admin page.
* Statuses: Open, Win, Loss — with colour-coded badges.
* Automatic net profit/loss per slip (payout − stake on wins, −stake on losses).
* Six shortcodes for front-end summaries.
* Configurable currency symbol.
* Secure: nonces + capability checks on every action, prepared SQL queries, output escaping.
* Clean uninstall (drops its table and options).

= Shortcodes =

* `[TotalBetAmount]` — total stake for the current month.
* `[BetsRatio]` — hit rate (wins / settled) for the current month.
* `[MonthlyWinnings]` — net profit/loss for the current month.
* `[YearlyWinnings]` — net profit/loss for the current year.
* `[HighestXAmountWin]` — biggest single win (by potential payout).
* `[OverallScore]` — all-time win/loss record.

== Installation ==

1. Upload the `betting-booking` folder to `/wp-content/plugins/`.
2. Activate **Betting Bookings** from the Plugins screen (this creates the database table).
3. Open the **Betting Bookings** menu to add slips and grab shortcodes.

== Changelog ==

= 2.0.0 =
* Fixed: main plugin file now has a .php extension so WordPress actually loads it.
* Fixed: database table is now created on activation (inserts previously failed silently).
* Fixed: Edit / Update / Remove buttons now work — the AJAX handlers were missing entirely.
* Added: all six documented shortcodes are implemented.
* Added: nonces + capability checks on the form and every AJAX action.
* Added: prepared statements and output escaping throughout.
* Added: per-slip and aggregate net profit/loss, pagination, currency option, modal styling, i18n.

= 1.0 =
* Initial prototype.
