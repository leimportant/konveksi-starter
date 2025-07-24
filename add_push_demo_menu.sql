-- SQL to add Push Notification Demo menu item
INSERT INTO menus (title, href, icon, parent_id, `order`, is_active, created_at, updated_at)
VALUES ('Push Notification Demo', '/push/demo', 'Bell', NULL, 20, 1, NOW(), NOW());

-- Assign the menu to all roles (optional)
INSERT INTO menus_role (menu_id, role_id, created_at, updated_at)
SELECT LAST_INSERT_ID(), id, NOW(), NOW() FROM roles;