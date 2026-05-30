<?php
/**
 * DEPRECATED: This view is no longer used.
 * Menu visibility is now handled server-side by Menu::getUserMenus()
 * which uses Gate for permission checks.
 *
 * @see Modules\Menu\Menu::getUserMenus()
 */
http_response_code(410);
echo json_encode(['error' => 'This endpoint is deprecated. Use /Menu/getUserMenus instead.']);
exit;

