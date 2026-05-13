<?php

namespace Library;

class DataView
{
    private array $columnLabels = [];

    /**
     * Display modern table header with Bootstrap 5 styling
     */
    public function displayTHead(array $headings, array $hidden = [], $hasAction = true): string
    {
        $this->columnLabels = [];
        $html = '<thead class="table-header-modern">';
        $html .= '<tr>';
        
        foreach ($headings as $heading) {
            $col = $heading['column'] ?? '';
            $label = $heading['label'] ?? '';
            
            if (in_array($col, $hidden)) {
                continue;
            }
            
            $this->columnLabels[$col] = $label;
            $html .= "<th class=\"text-uppercase fw-semibold text-muted\" style=\"font-size: 0.75rem; letter-spacing: 0.5px;\">{$label}</th>";
        }
        
        if ($hasAction) {
            $html .= '<th class="text-uppercase fw-semibold text-muted text-center" style="font-size: 0.75rem; letter-spacing: 0.5px; width: 120px;">Actions</th>';
        }
        
        $html .= '</tr>';
        $html .= '</thead>';
        
        return $html;
    }

    /**
     * Display modern table body with hover effects and action buttons
     */
    public function displayTBody(array $records, string $class, string $table, array $hidden = [], array $actions = [], $labelSize = 'big'): string
    {
        if (empty($records)) {
            return $this->renderEmptyState();
        }

        $html = '<tbody>';
        
        foreach ($records as $index => $row) {
            $rowValue = $row['txt_row_value'] ?? $row['id'] ?? '';
            
            // Add subtle animation delay for each row
            $animationDelay = min($index * 0.03, 0.5);
            
            $html .= "<tr class=\"table-row-modern\" style=\"animation-delay: {$animationDelay}s;\">";
            
            foreach ($row as $key => $value) {
                if (in_array($key, $hidden) || is_int($key)) {
                    continue;
                }
                
                $label = htmlspecialchars($this->columnLabels[$key] ?? $key, ENT_QUOTES);
                $formattedValue = $this->formatCellValue($value, $key);
                $html .= "<td class=\"align-middle\" data-label=\"{$label}\">{$formattedValue}</td>";
            }

            // Actions Column
            if (!empty($actions)) {
                $html .= '<td class="align-middle text-center" data-label="Actions">';
                $html .= $this->renderActions($actions, $rowValue);
                $html .= '</td>';
            }

            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        return $html;
    }

    /**
     * Render actions: Use dropdown if more than 3 actions
     */
    private function renderActions(array $actions, string $rowValue): string
    {
        if (count($actions) <= 3) {
            $html = '<div class="btn-group btn-group-sm action-buttons" role="group">';
            foreach ($actions as $action) {
                $html .= $this->renderActionButton($action, $rowValue);
            }
            $html .= '</div>';
            return $html;
        }

        // Dropdown for many actions
        $html = '<div class="dropdown">';
        $html .= '<button class="btn btn-light btn-sm rounded-pill px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
        $html .= '<i class="fa fa-ellipsis-h text-muted"></i>';
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2">';
        foreach ($actions as $action) {
            $html .= $this->renderDropdownItem($action, $rowValue);
        }
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    private function renderActionButton(array $action, string $rowValue): string
    {
        $btnClass = $this->mapButtonColor($action['color'] ?? 'primary');
        $btnIcon = $action['icon'] ?? 'fa-edit';
        $btnName = $action['name'] ?? 'Action';
        $type = $action['type'] ?? 'edit';
        
        $escapedValue = htmlspecialchars($rowValue, ENT_QUOTES);
        $escapedTitle = htmlspecialchars($btnName, ENT_QUOTES);
        
        return "
        <button 
            class='btn btn-{$btnClass} btn-action' 
            onclick=\"angular.element(this).scope().{$type}('{$escapedValue}')\" 
            title='{$escapedTitle}'
            data-bs-toggle='tooltip'>
            <i class='fa {$btnIcon}'></i>
        </button>";
    }

    private function renderDropdownItem(array $action, string $rowValue): string
    {
        $icon = $action['icon'] ?? 'fa-circle';
        $name = $action['name'] ?? 'Action';
        $type = $action['type'] ?? 'edit';
        $color = strtolower($action['color'] ?? '');
        $textClass = ($color === 'danger') ? 'text-danger' : '';

        $escapedValue = htmlspecialchars($rowValue, ENT_QUOTES);
        
        return "
        <li>
            <a class='dropdown-item rounded-3 {$textClass}' href='javascript:void(0)' onclick=\"angular.element(this).scope().{$type}('{$escapedValue}')\">
                <i class='fa {$icon} fa-fw me-2 opacity-75'></i> {$name}
            </a>
        </li>";
    }

    /**
     * Render empty state with icon and message
     */
    private function renderEmptyState(): string
    {
        return '
        <tbody>
            <tr>
                <td colspan="100%" class="text-center py-5">
                    <div class="empty-state py-4">
                        <div class="empty-state__icon mb-4">
                            <i class="fa fa-folder-open fa-3x text-muted opacity-25"></i>
                        </div>
                        <h5 class="text-main fw-bold mb-1">No results found</h5>
                        <p class="text-muted small">We couldn\'t find any records matching your current criteria.</p>
                    </div>
                </td>
            </tr>
        </tbody>';
    }

    /**
     * Format cell value based on content type
     */
    private function formatCellValue($value, string $key): string
    {
        if ($value === null || $value === '') {
            return '<span class="text-muted fst-italic opacity-50">—</span>';
        }

        $keyLower = strtolower($key);

        // Name columns -> Add Avatar
        if (str_contains($keyLower, 'name') || str_contains($keyLower, 'user') || str_contains($keyLower, 'applicant')) {
            return $this->formatWithNameAvatar((string)$value);
        }

        // Amount / Price / Balance -> Currency
        if (str_contains($keyLower, 'amount') || str_contains($keyLower, 'price') || str_contains($keyLower, 'revenue') || str_contains($keyLower, 'total')) {
            return $this->formatCurrency($value);
        }

        // Status
        if (str_contains($keyLower, 'status') || str_contains($keyLower, 'state')) {
            return $this->formatStatusBadge($value);
        }

        // Date
        if (str_contains($keyLower, 'date') || str_contains($keyLower, 'created') || str_contains($keyLower, 'updated')) {
            return $this->formatDate($value);
        }

        // Boolean
        if (str_starts_with($keyLower, 'bit_') || in_array(strtolower((string)$value), ['yes', 'no', 'true', 'false', '1', '0'])) {
            return $this->formatBoolean($value);
        }

        return htmlspecialchars((string)$value, ENT_QUOTES);
    }

    private function formatWithNameAvatar(string $name): string
    {
        $initials = strtoupper(substr($name, 0, 1));
        $bgColors = ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
        $bg = $bgColors[ord($initials) % count($bgColors)];

        $avatar = "<div class='d-inline-flex align-items-center justify-content-center rounded-circle me-2 text-white fw-bold' 
                        style='width: 32px; height: 32px; font-size: 0.8rem; background-color: {$bg}; box-shadow: 0 2px 4px rgba(0,0,0,0.1)'>
                        {$initials}
                   </div>";
        
        return "<div class='d-flex align-items-center fw-medium text-main'>
                    {$avatar}
                    <span>" . htmlspecialchars($name, ENT_QUOTES) . "</span>
                </div>";
    }

    private function formatCurrency($value): string
    {
        $num = (float)$value;
        $formatted = number_format($num, 2);
        
        $color = $num < 0 ? 'text-danger' : 'text-main';
        $symbol = defined('ZT_CURRENCY_SYMBOL') ? ZT_CURRENCY_SYMBOL : 'TZS';

        return "<div class='{$color} fw-bold d-flex align-items-baseline'>
                    <small class='opacity-50 me-1' style='font-size: 0.7rem'>{$symbol}</small>
                    <span>{$formatted}</span>
                </div>";
    }

    /**
     * Format status as colored badge
     */
    private function formatStatusBadge(string $status): string
    {
        $statusLower = strtolower($status);
        
        $badgeClass = match(true) {
            str_contains($statusLower, 'active') || str_contains($statusLower, 'approved') || str_contains($statusLower, 'success') || str_contains($statusLower, 'paid') => 'success',
            str_contains($statusLower, 'pending') || str_contains($statusLower, 'waiting') || str_contains($statusLower, 'partial') => 'warning',
            str_contains($statusLower, 'inactive') || str_contains($statusLower, 'denied') || str_contains($statusLower, 'rejected') || str_contains($statusLower, 'error') => 'danger',
            str_contains($statusLower, 'draft') || str_contains($statusLower, 'disabled') || str_contains($statusLower, 'closed') => 'secondary',
            default => 'primary'
        };

        return "<span class='badge bg-{$badgeClass} bg-opacity-10 text-{$badgeClass} border border-{$badgeClass} border-opacity-10 fw-semibold px-2 py-1' style='font-size: 0.725rem; border-radius: 6px;'>
                   <i class='fa fa-circle me-1' style='font-size: 0.4rem; vertical-align: middle'></i>" . 
                   htmlspecialchars($status, ENT_QUOTES) . 
               "</span>";
    }

    /**
     * Format date in a readable format
     */
    private function formatDate(string $date): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return htmlspecialchars($date, ENT_QUOTES);
        }

        $formatted = date('d M Y', $timestamp);
        $time = date('H:i', $timestamp);
        
        return "<div class='text-nowrap' title='{$date}'>
                    <div class='text-main' style='font-size: 0.875rem'>{$formatted}</div>
                    <div class='text-muted' style='font-size: 0.75rem'><i class='fa fa-clock fa-xs me-1 opacity-50'></i>{$time}</div>
                </div>";
    }

    /**
     * Format boolean as icon
     */
    private function formatBoolean($value): string
    {
        $isTrue = in_array(strtolower((string)$value), ['yes', 'true', '1', 'active']);
        
        if ($isTrue) {
            return '<div class="badge bg-success-subtle text-success rounded-circle p-1" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa fa-check" style="font-size: 0.7rem"></i>
                    </div>';
        }
        
        return '<div class="badge bg-danger-subtle text-danger rounded-circle p-1" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa fa-times" style="font-size: 0.7rem"></i>
                </div>';
    }

    /**
     * Map legacy button colors to Bootstrap 5 colors
     */
    private function mapButtonColor(string $color): string
    {
        return match(strtolower($color)) {
            'default', 'secondary' => 'light',
            'primary', 'info' => 'soft-primary',
            'success' => 'soft-success',
            'warning' => 'soft-warning',
            'danger' => 'soft-danger',
            default => 'soft-primary'
        };
    }

    /**
     * Generate complete table wrapper with modern styling
     */
    public function renderTable(array $headings, array $records, array $actions = [], array $hidden = [], array $options = []): string
    {
        $hasAction = !empty($actions);
        $html = '<div class="zt-card zt-table-card overflow-hidden shadow-sm border-0 mb-4">';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-hover align-middle mb-0 zt-table-responsive">';
        $html .= $this->displayTHead($headings, $hidden, $hasAction);
        $html .= $this->displayTBody($records, '', '', $hidden, $actions);
        $html .= '</table>';
        $html .= '</div>';
        
        // Pagination Footer Placeholder
        if (!empty($records)) {
            $html .= '<div class="table-footer px-4 py-3 border-top d-flex align-items-center justify-content-between">';
            $html .= '<small class="text-muted">Showing ' . count($records) . ' records</small>';
            $html .= '<nav aria-label="Table pagination">';
            $html .= '<ul class="pagination pagination-sm mb-0">';
            $html .= '<li class="page-item disabled"><a class="page-link border-0 rounded-3 me-1" href="#"><i class="fa fa-chevron-left"></i></a></li>';
            $html .= '<li class="page-item active"><a class="page-link border-0 rounded-3 me-1" href="#">1</a></li>';
            $html .= '<li class="page-item"><a class="page-link border-0 rounded-3" href="#"><i class="fa fa-chevron-right"></i></a></li>';
            $html .= '</ul>';
            $html .= '</nav>';
            $html .= '</div>';
        }

        $html .= '</div>';
        
        return $html;
    }

    /**
     * Get CSS styles for modern table
     */
    public static function getStyles(): string
    {
        return "
        <style>
            .table-card {
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.4);
                border-radius: 24px;
                box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
            }

            .table-header-modern {
                background: rgba(var(--zt-primary-rgb), 0.03);
            }

            .table-header-modern th {
                padding: 1.25rem 1.5rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                color: #64748b;
                font-weight: 700;
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .table-row-modern {
                animation: fadeInUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) backwards;
                transition: all 0.3s ease;
            }

            @keyframes fadeInUp {
                from { opacity: 0; transform: translateY(12px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .table-row-modern:hover {
                background-color: rgba(var(--zt-primary-rgb), 0.02) !important;
            }

            .table-row-modern td {
                padding: 1.125rem 1.5rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            }

            /* Soft Buttons for Table Actions */
            .btn-soft-primary { background-color: rgba(99, 102, 241, 0.1); color: #6366f1; border: none; }
            .btn-soft-success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: none; }
            .btn-soft-warning { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; border: none; }
            .btn-soft-danger { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: none; }
            
            .btn-action {
                width: 34px;
                height: 34px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                transition: all 0.2s ease;
            }

            .btn-action:hover {
                transform: translateY(-2px);
                filter: brightness(0.95);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            /* Custom Dropdowns */
            .dropdown-item {
                font-weight: 500;
                font-size: 0.85rem;
                padding: 0.6rem 1rem;
                transition: all 0.2s ease;
            }

            .dropdown-item:hover {
                background-color: rgba(var(--zt-primary-rgb), 0.05);
                color: var(--zt-primary);
                transform: translateX(4px);
            }

            .text-main { color: #1e293b; }
            
            /* Responsive Adjustments */
            @media (max-width: 768px) {
                .table-row-modern td { padding: 0.875rem 1rem; }
            }
        </style>";
    }
}