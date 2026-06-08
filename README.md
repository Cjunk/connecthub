# ConnectHub

ConnectHub is a PHP/PostgreSQL community events platform inspired by Meetup, built for groups, organisers, members, events, memberships, and live community activity.

![Status](https://img.shields.io/badge/Status-In%20Development-yellow)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)
![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL-blue)
![Bootstrap](https://img.shields.io/badge/Frontend-Bootstrap%205-purple)
![Stripe](https://img.shields.io/badge/Payments-Stripe-green)

---

## Features

### Currently Available

* User registration and login
* Role-based access control
* Member, organiser, admin-style permissions
* Group creation and management
* Group ownership and role hierarchy
* Event creation and event detail pages
* Event cover image upload support
* Event browsing interface
* Membership/payment flow using Stripe
* Responsive Bootstrap-based interface
* Flash message/user feedback system
* PostgreSQL database integration
* Cleaned project structure with separated config, assets, scripts, docs, and source code
* Dashboard live activity feed foundation
* Activity feed API endpoint
* Activity feed model and service foundation
* Dummy activity feed seed data
* Dashboard feed items linked to groups/events by slug
* Dashboard photo/lightbox support

### In Development

* Real activity feed recording when users create comments, events, groups, uploads, and joins
* User profile management
* Admin dashboard/control panel
* Email notifications and event reminders
* More polished mobile dashboard experience
* Production deployment hardening

### Planned Features

* Advanced event and group search
* Public/private group visibility rules
* Member skill/experience tracking
* Event safety notes, checklists, or waivers
* Photo posting and group media feed
* Calendar integration
* Mobile app or PWA support

---

## Tech Stack

* Backend: PHP 8+
* Database: PostgreSQL locally, production-ready config support
* Frontend: Bootstrap 5, custom CSS, JavaScript
* Payments: Stripe
* Hosting target: GoDaddy shared hosting / public web root style deployment
* Version control: Git/GitHub

---

## Project Structure

```text
connecthub/
├── api/                    # JSON/API endpoints
│   └── activity-feed.php
├── assets/                 # CSS, JavaScript, images
│   ├── css/
│   ├── js/
│   └── images/
├── auth/                   # Auth compatibility endpoints
├── config/                 # Bootstrap, constants, database, environment templates
├── database/               # Migrations and seed data
│   ├── migrations/
│   └── seeds/
├── docs/                   # Project notes and documentation
├── payment/                # Payment-related endpoints
├── scripts/                # Setup, debug, maintenance, deployment utilities
├── src/                    # Application source code
│   ├── controllers/
│   ├── helpers/
│   ├── models/
│   ├── services/
│   └── views/
├── storage/                # Local/generated/runtime storage
├── uploads/                # Runtime upload structure only
├── index.php               # Public route
├── dashboard.php           # Dashboard route
├── events.php              # Events route
├── groups.php              # Groups route
├── login.php               # Login route
├── register.php            # Register route
└── README.md
```

---

## Important Security Notes

The following files must not be committed:

```text
.env
production_config.php
_archive/
uploads/events/*
uploads/groups/*
uploads/profiles/*
uploads/documents/*
```

Tracked upload files should only preserve folder structure and upload rules, such as:

```text
uploads/.gitkeep
uploads/.htaccess
uploads/README.md
uploads/events/.gitkeep
uploads/groups/.gitkeep
uploads/profiles/.gitkeep
uploads/documents/.gitkeep
```

Real production credentials belong only on the server or local machine, not in Git.

---

## Activity Feed

ConnectHub now includes the foundation for a dashboard activity feed.

Current files:

```text
database/migrations/create_activity_feed_table.sql
database/seeds/activity_feed_dummy_data.sql
src/models/ActivityFeed.php
src/services/ActivityFeedService.php
api/activity-feed.php
assets/js/dashboard-feed.js
```

The dashboard currently fetches feed data from:

```text
/api/activity-feed.php
```

Feed items can include:

* event created
* group created
* comment created
* photo uploaded
* member joined

The next development step is wiring real user actions into `ActivityFeedService`.

---

## Local Development

Typical local flow:

```powershell
git status
php -S localhost:8080
```

Then test:

```text
http://localhost:8080/
http://localhost:8080/login.php
http://localhost:8080/register.php
http://localhost:8080/dashboard.php
http://localhost:8080/api/activity-feed.php
http://localhost:8080/groups.php
http://localhost:8080/events.php
```

---

## Database

The app uses PostgreSQL locally.

Activity feed migration:

```text
database/migrations/create_activity_feed_table.sql
```

Activity feed dummy seed:

```text
database/seeds/activity_feed_dummy_data.sql
```

---

## Deployment Notes

This project currently uses a root/web-directory PHP layout suitable for GoDaddy-style hosting.

Important:

* Do not deploy `.env` from Git.
* Do not deploy real credentials from Git.
* Keep `production_config.php` manually configured on the server.
* Make sure new assets and view files are uploaded.
* Make sure database migrations are applied before using new features.

---

## Current Development Status

Approximate status:

* Foundation: mostly complete
* Authentication: working
* Groups: working foundation
* Events: working foundation
* Payments: test-mode foundation
* Dashboard: active redesign in progress
* Activity feed: API/model/table foundation complete, real action recording in progress
* Admin tools: incomplete
* Email notifications: planned
* Production hardening: pending

---

## Suggested Next Steps

1. Wire real event comments into `activity_feed`.
2. Wire event creation into `activity_feed`.
3. Wire group creation into `activity_feed`.
4. Add activity feed rate-limiting and cleanup rules.
5. Finish dashboard responsive polish.
6. Test full local flow.
7. Deploy to staging/test folder before touching production.
8. Only deploy to live after full testing.

---

## License

See `LICENSE`.
