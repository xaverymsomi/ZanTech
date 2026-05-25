<?php

namespace View;

/**
 * Global UI Component Engine
 * Provides standardized, premium UI elements for use across the system.
 */
class Component
{
    /**
     * Render a modern Statistic Card
     */
    public static function statsCard(string $label, $value, string $icon, string $color = 'primary', string $trend = ''): string
    {
        $trendHtml = $trend !== '' ? "<small class='text-success fw-bold ms-2'><i class='fa fa-arrow-up me-1'></i>{$trend}</small>" : "";
        
        return "
        <div class='zt-card stats-card border-0 shadow-sm p-4 h-100 overflow-hidden position-relative'>
            <div class='d-flex align-items-center justify-content-between mb-3'>
                <div class='stats-icon bg-{$color} bg-opacity-10 text-{$color} rounded-3 p-3'>
                    <i class='fa {$icon} fa-2x'></i>
                </div>
            </div>
            <div>
                <h6 class='text-muted text-uppercase fw-bold mb-1' style='font-size: 0.7rem; letter-spacing: 1px;'>{$label}</h6>
                <div class='d-flex align-items-baseline'>
                    <h3 class='fw-bold mb-0 text-main'>{$value}</h3>
                    {$trendHtml}
                </div>
            </div>
            <div class='position-absolute top-0 end-0 opacity-05 pointer-events-none' style='font-size: 8rem; transform: translate(25%, -25%);'>
                <i class='fa {$icon}'></i>
            </div>
        </div>";
    }

    /**
     * Render a standardized Breadcrumb trail
     */
    public static function breadcrumbs(array $items): string
    {
        $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-4">';
        $html .= '<li class="breadcrumb-item"><a href="' . URL . '/dashboard" class="text-decoration-none text-muted"><i class="fa fa-home"></i></a></li>';
        
        foreach ($items as $label => $link) {
            if ($link === null) {
                $html .= "<li class='breadcrumb-item active fw-semibold text-main' aria-current='page'>{$label}</li>";
            } else {
                $html .= "<li class='breadcrumb-item'><a href='" . URL . "/{$link}' class='text-decoration-none text-muted'>{$label}</a></li>";
            }
        }
        
        $html .= '</ol></nav>';
        return $html;
    }

    /**
     * Render a premium Section Header
     */
    public static function sectionHeader(string $title, string $subtitle = '', array $actions = []): string
    {
        $html = '<div class="d-flex align-items-center justify-content-between mb-4">';
        $html .= '<div>';
        $html .= "<h3 class='fw-bold text-main mb-1'>{$title}</h3>";
        if ($subtitle !== '') {
            $html .= "<p class='text-muted small mb-0'>{$subtitle}</p>";
        }
        $html .= '</div>';

        if (!empty($actions)) {
            $html .= '<div class="d-flex gap-2">';
            foreach ($actions as $action) {
                $html .= $action;
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Render a standard Module Card
     */
    public static function card(string $content, string $title = '', string $footer = ''): string
    {
        $headerHtml = $title !== '' ? "<div class='card-header bg-white border-bottom-0 pt-4 px-4'><h5 class='fw-bold mb-0'>{$title}</h5></div>" : "";
        $footerHtml = $footer !== '' ? "<div class='card-footer bg-light border-top-0 py-3 px-4'>{$footer}</div>" : "";

        return "
        <div class='card border-0 shadow-sm rounded-4 overflow-hidden mb-4'>
            {$headerHtml}
            <div class='card-body p-4'>
                {$content}
            </div>
            {$footerHtml}
        </div>";
    }

    /**
     * Render a standardized Data Table
     */
    public static function table(array $headers, array $rows, string $id = ''): string
    {
        $idAttr = $id !== '' ? "id='{$id}'" : "";
        $html = "<div class='table-responsive'><table {$idAttr} class='table table-hover align-middle border-light'>";
        $html .= "<thead class='table-light text-muted small text-uppercase'><tr class='border-transparent'>";
        foreach ($headers as $h) {
            $html .= "<th class='fw-bold border-0 py-3'>{$h}</th>";
        }
        $html .= "</tr></thead><tbody class='border-top-0'>";
        
        foreach ($rows as $row) {
            $html .= "<tr>";
            foreach ($row as $cell) {
                $html .= "<td class='py-3'>{$cell}</td>";
            }
            $html .= "</tr>";
        }
        
        $html .= "</tbody></table></div>";
        return $html;
    }
}
