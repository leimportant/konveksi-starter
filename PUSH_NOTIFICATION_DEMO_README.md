# Push Notification Demo Setup

## Adding the Menu Item

We've provided several ways to add the Push Notification Demo menu item to the navigation:

### Option 1: Run the Migration

This is the recommended approach:

```bash
php artisan migrate --path=database/migrations/2025_07_20_000001_add_push_notification_demo_menu.php
```

### Option 2: Run the Seeder

If the migration doesn't work, you can run the seeder:

```bash
php artisan db:seed --class=PushNotificationMenuSeeder
```

### Option 3: Run the PHP Script

You can also run the standalone PHP script:

```bash
php add_push_demo_menu.php
```

### Option 4: Execute SQL Directly

If none of the above methods work, you can run the SQL directly in your database:

```sql
-- SQL to add Push Notification Demo menu item
INSERT INTO menus (title, href, icon, parent_id, `order`, is_active, created_at, updated_at)
VALUES ('Push Notification Demo', '/push/demo', 'Bell', NULL, 20, 1, NOW(), NOW());

-- Assign the menu to all roles (optional)
INSERT INTO menus_role (menu_id, role_id, created_at, updated_at)
SELECT LAST_INSERT_ID(), id, NOW(), NOW() FROM roles;
```

### Option 5: Windows Batch File

For Windows users, we've included a batch file that will run the migration:

```
add_push_menu.bat
```

## Verifying the Setup

After adding the menu item, you should see a "Push Notification Demo" link in your navigation menu. Clicking on it will take you to the push notification demo page where you can test push notifications.