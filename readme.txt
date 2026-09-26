=== Zeko Freelance ===
Contributors: zeko
Tags: freelance, marketplace, escrow, contracts, projects
Requires at least: 5.8
Tested up to: 7.1.2
Requires PHP: 7.4
Stable tag: 0.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Freelance marketplace for the Zeko ecosystem: project listings, bidding, contracts, milestone-based escrow payments, disputes, portfolios, and reviews.

== Description ==

Zeko Freelance is a freelance marketplace for the Zeko ecosystem. Clients post projects, freelancers bid, and work is paid out milestone-by-milestone through escrow backed by Zeko Pay.

= Features =

* **Project Posting** — Rich project listings with budgets, categories, and required skills
* **Marketplace Filters** — Browse projects by category, budget, and skill
* **Bidding** — Freelancers submit proposals with cover notes and quoted amounts
* **Awarding & Contracts** — Clients award projects and work moves into a formal contract
* **Milestone Payments** — Submit, review, and approve milestones; funds are held in escrow and released via Zeko Pay
* **Disputes** — Raise and resolve disputes with a refund/rollback path for escrowed funds
* **Portfolios** — Freelancer portfolio pages to showcase past work
* **Verification** — Freelancer verification badges
* **Reviews & Ratings** — Post-contract reviews between clients and freelancers
* **Commission Engine** — Configurable platform fee deducted at milestone release

= Shortcodes =

* `[zeko_freelance]` — Marketplace landing page
* `[zeko_freelance_projects]` — Project listings with filters
* `[zeko_freelance_project]` — Single project view with bidding
* `[zeko_freelance_post_project]` — Project posting form
* `[zeko_freelance_profile]` — Freelancer profile
* `[zeko_freelance_portfolios]` — Portfolio showcase

== Installation ==

1. Upload the `zeko-freelance` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Activate Zeko Pay to enable escrow, milestone releases, and commission journaling
4. Create pages and add the shortcodes above

== Frequently Asked Questions ==

= Is escrow required? =

Milestone funding and release use Zeko Pay escrow by default. When Zeko Pay is not active the plugin degrades to manual milestone tracking.

= How are fees calculated? =

A configurable platform fee (default 10%) is deducted from the freelancer's milestone release and journaled as a fee via Zeko Pay.

== Dependencies ==

Zeko Freelance runs standalone. Zeko Pay powers escrow, milestone releases, and commission journaling; without it the plugin degrades to manual milestone tracking.

== Privacy ==

Data stored includes projects, bids, contracts, milestones, disputes, portfolios, reviews, and payment references. Project view counts use a hashed identifier derived from user ID/IP; no raw IP is stored for views. Data you submit is removed when you request erasure and on uninstall (see below).

== External Services ==

Zeko Freelance makes no outbound requests. Escrow and milestone money movement are handled locally through the Zeko Pay SDK and gateway configuration.

== Uninstall ==

Deleting the plugin drops all Zeko Freelance tables, clears the daily cron, removes only the pages it created, and deletes its options.

== Troubleshooting ==

* Escrow not capturing funds? Confirm Zeko Pay is active and the client funded the milestone before work starts.
* Milestone auto-approved in error? Disable auto-approve under Zeko Freelance > Settings > Milestone SLA timers.
* Dispute wants refund? Use the dispute refund/rollback path so escrowed funds are returned via Zeko Pay.

== Screenshots ==

1. Freelance dashboard with proposals and escrow milestones


== Changelog ==

= 0.5.0 =
* Milestone SLA timers — review and submission deadlines with daily cron processing
* Auto-approve overdue review milestones (configurable, skipped on disputed contracts)
* Auto-dispute overdue funded milestones (admins notified)
* Idempotent review/submission reminder emails
* New "Milestone SLA timers" admin settings section (days/hours + toggleable automation)

= 0.4.0 =
* Demo data seeder — generate/clear realistic marketplace data from the admin settings page (Zeko Freelance → "Demo data" section)
* Idempotent seeding via version flag

= 0.3.0 =
* Initial marketplace release
* Project posting, browsing, and bidding
* Awards and contracts
* Milestone-based escrow payments through Zeko Pay
* Disputes with refund/rollback
* Portfolios, verification, and reviews
* Commission engine

== Upgrade Notice ==

= 0.5.0 =
Milestone SLA timers: overdue milestones are auto-approved or disputed per the new settings. Review the default timeframes under Zeko Freelance > Settings > Milestone SLA timers.

= 0.3.0 =
Initial release of Zeko Freelance.
