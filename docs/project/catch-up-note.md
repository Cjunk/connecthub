# ConnectHub Catch-Up Note

## Current branch

Working branch:

project-cleanup

Do not merge to main yet. Keep testing locally first.

## Current status

The repo has been heavily cleaned and pushed to GitHub.

Major cleanup completed:

- Root folder cleaned.
- Debug/setup/test files moved into scripts/.
- Old junk/archive folders removed from Git tracking.
- _archive remains local only.
- Runtime uploads removed from Git tracking.
- production_config.php is local-only and untracked.
- Exposed production DB password was changed.
- Old password removed from current project files.
- Old relative includes cleaned.
- Dashboard CSS moved to assets/css/dashboard.css.
- Dashboard JS moved to assets/js/dashboard.js.
- Dashboard markup moved to src/views/dashboard/index.php.
- Safe page CSS moved into assets/css/.
- Safe create page JS moved into assets/js/.
- Auth view include paths fixed.
- Activity feed table/model/API added.
- Dashboard live feed connected to /api/activity-feed.php.
- Activity feed items now show group/event names.
- Activity feed items link to group/event pages using slugs.
- Dashboard centre feed layout started.
- Dashboard sidebars/menu/responsive work started.
- Dashboard photo lightbox was fixed after layout changes.

## Current activity feed files

Key files:

- database/migrations/create_activity_feed_table.sql
- database/seeds/activity_feed_dummy_data.sql
- src/models/ActivityFeed.php
- api/activity-feed.php
- assets/js/dashboard-feed.js
- src/views/dashboard/index.php
- assets/css/dashboard.css

## Current dashboard behaviour

Dashboard now has:

- Left side area for user/groups/actions.
- Centre live activity feed.
- Right side for upcoming events/photos.
- Responsive menu behaviour on smaller screens.
- Photo lightbox working again.
- Feed items clickable through to event/group pages.

## Important warnings

Do not commit:

- .env
- production_config.php
- _archive/
- uploads/events/*
- uploads/groups/*
- uploads/profiles/*
- uploads/documents/*

## Next safe steps

Recommended next session:

1. Full local test pass.
2. Check dashboard on desktop/tablet/mobile widths.
3. Polish dashboard responsive menu/header spacing.
4. Start writing real activity_feed rows when real actions happen:
   - event_created
   - group_created
   - comment_created
   - photo_uploaded
   - member_joined
5. Do not merge project-cleanup into main until local testing is solid.

## Test URLs

Test these locally:

- /
- /login.php
- /register.php
- /dashboard.php
- /api/activity-feed.php
- /groups.php
- /events.php
- /create-group.php
- /create-event.php
- /membership.php
