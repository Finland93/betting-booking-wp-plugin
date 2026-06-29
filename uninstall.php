# Betting Bookings — WordPress Plugin

Maintain a **personal** record of your betting slips on a yearly, monthly and weekly basis. Track stake, row count, potential win, hit count and outcome, and display summaries anywhere on your site with shortcodes.

> This is a personal bookkeeping tool only. It does **not** place bets, take payments, or connect to any bookmaker.

## Features

- Add, edit and remove slips from a single admin page.
- Statuses: **Open**, **Win**, **Loss** with colour-coded badges.
- Automatic **net profit/loss** per slip and in aggregate (payout − stake on wins, −stake on losses).
- Six front-end **shortcodes** (see below).
- Configurable **currency symbol**.
- Security throughout: nonces + capability checks on every action, **prepared SQL** queries, output escaping.
- Pagination for the slip table and a clean uninstall (drops its own table + options).

## Installation

1. Upload the `betting-booking` folder to `/wp-content/plugins/`.
2. Activate **Betting Bookings** in **Plugins** (this creates the database table).
3. Open the **Betting Bookings** admin menu to add slips and copy shortcodes.

## Shortcodes

| Shortcode | Shows |
| --- | --- |
| `[TotalBetAmount]` | Total stake for the current month |
| `[BetsRatio]` | Hit rate (wins / settled) for the current month |
| `[MonthlyWinnings]` | Net profit/loss for the current month |
| `[YearlyWinnings]` | Net profit/loss for the current year |
| `[HighestXAmountWin]` | Biggest single win (by potential payout) |
| `[OverallScore]` | All-time win/loss record |

## What changed in 2.0.0

The 1.0 prototype did not run. This release makes it a working plugin:

- **`.php` extension added** to the main file so WordPress loads it at all.
- **Database table created on activation** (1.0 inserted into a table that never existed).
- **Edit / Update / Remove now work** — the AJAX handlers they called were never registered.
- **All six shortcodes implemented** (none existed before).
- **Nonces, capability checks, prepared statements and escaping** added everywhere.
- Added net profit/loss, pagination, currency option, modal styling and i18n.

## License

GPLv2 or later — see [LICENSE](LICENSE).

**Author:** [Finland93](https://github.com/Finland93)
