# Presenting FoodShare

## Europass project entry

**FoodShare — full-stack food redistribution project**

**Technologies:** PHP, MySQL-compatible InnoDB, HTML/CSS, Git, GitHub Actions, Node.js integration tests.

- Developed a role-based application for listing surplus food, requesting pickup, approving a receiver and confirming collection.
- Implemented a transactional reservation workflow with a database uniqueness constraint and exercised competing requests through independent HTTP server processes.
- Added a migration preflight, private photo handling and a monthly receipt report that distinguishes measured and unweighed pickups. Documented deployment, recovery and a partner pilot evaluation protocol.

Use these bullets to describe the project scope. When describing personal work, select only contributions you can demonstrate and explain. Credit collaborators and AI assistance according to the application or institution's rules. Add dates, links and your role from your own records.

## LinkedIn project description

FoodShare helps a donor and receiver coordinate a surplus-food pickup from listing to confirmed receipt. The PHP and MySQL-compatible application includes reservation controls, private pickup instructions and an aggregate report of confirmed collections and reported weight. Its integration suite tests what happens when two receivers request the same food. The project includes a proposed pilot protocol for evaluating food access and waste reduction in a community setting.

## Evidence to show by audience

| Audience | Presentable evidence now | Stronger evidence to earn next |
| --- | --- | --- |
| International Master's and scholarships | A one-page problem statement, the state diagram, the reservation invariant and a candid account of missing impact evidence. | A partner-reviewed pilot report, a clearly attributed leadership decision, supervisor feedback and a documented design revision following field observation. |
| Full-stack engineering | Reproduce the guard against double booking, trace the migration, show a mobile form and explain a failed transaction. | A green hosted CI run, code review, versioned release, staging restore and measured latency under a published workload. |
| AI/ML and research | Frame an evaluable question: can eligibility-aware pickup ranking improve timely collections over earliest-expiry ordering without increasing travel or concentrating access? | Consent and data dictionary, time-separated train/test periods, leakage analysis, baseline comparison, uncertainty and partner-level error analysis. Report a negative result if warranted. |
| Social innovation or entrepreneurship | Explain the kitchen's closing-time constraint and the receiver's transport and staffing burden. Show how the coordinator resolves a failed pickup. | Interview summaries shared with permission, evidence of repeat use, coordinator minutes and operating cost per completed collection. Estimate sustainability only after observing costs. |

## Demo and outreach plan

Record a captioned three-minute demo using a synthetic database. Spend 20 seconds on the donor and receiver problem, 90 seconds on posting, approval and confirmation in separate browser profiles, 30 seconds on the competing-request test and the monthly report, and 40 seconds on what a partner would validate. Label all demo records synthetic. Link to the exact commit and test workflow run. A short slide sequence can follow the same order with a final slide for study limitations and the next decision.

For a technical article, explain why a transaction without an exclusive claim was insufficient, show the state diagram and report the regression test. For outreach, invite a kitchen lead, a receiving organization and a university supervisor to review a synthetic demonstration and identify one real scheduling constraint. Ask permission before naming any partner. A genuine collaboration is demonstrated by documented decisions, feedback and follow-through.

Do not list a live deployment, independent security assessment, ML model, scholarship, partner, impact total or research publication without its corresponding evidence. Use the exact tested branch or release link so a reviewer can distinguish delivered work from the roadmap.
