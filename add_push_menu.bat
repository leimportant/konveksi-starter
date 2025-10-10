@echo off
echo Running migration to add Push Notification Demo menu...
php artisan migrate --path=database/migrations/2025_07_20_000001_add_push_notification_demo_menu.php

echo.
echo If the migration failed, you can try running the seeder instead:
echo php artisan db:seed --class=PushNotificationMenuSeeder

echo.
echo Alternatively, you can run the PHP script directly:
echo php add_push_demo_menu.php

echo.
echo Done!
pause