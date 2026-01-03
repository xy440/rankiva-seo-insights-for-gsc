<?php
if (!defined('ABSPATH')) exit;

function scso_expected_ctr($position) {
    if ($position <= 1)  return 30;
    if ($position <= 3)  return 10;
    if ($position <= 5)  return 5;
    if ($position <= 10) return 4;
    if ($position <= 20) return 2;
    return 1;
}

/**
 * Determine if a row is a "Money Post"
 */
function scso_is_money_post($row) {
    if (empty($row)) {
        return false;
    }

    $impressions = (int) ($row->impressions ?? 0);
    $position    = (float) ($row->avg_position ?? 0);
    $score       = (int) ($row->opportunity_score ?? 0);
    $updated     = isset($row->marked_updated) ? (int) $row->marked_updated : 0;

    if ($updated === 1) {
        return false;
    }

    return (
        $impressions >= 100 &&
        $position >= 5 &&
        $position <= 15 &&
        $score >= 60
    );
}

function scso_ranking_reason(float $position): string {

    if ($position <= 3) {
        return 'Already ranking in top 3';
    }

    if ($position <= 5) {
        return 'Ranking #' . number_format($position, 1) . ', close to top 3';
    }

    if ($position <= 10) {
        return 'Ranking #' . number_format($position, 1) . ', can improve to top 5';
    }

    if ($position <= 20) {
        return 'Ranking #' . number_format($position, 1) . ', could reach page 1';
    }

    return 'Low visibility';
}

/**
 * Short time format: 5m, 2h, 3d, 1w, 2mo, 1y
 */
function scso_short_time_diff($timestamp) {
    if (empty($timestamp)) {
        return 'Never';
    }
    
    $time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $diff = current_time('timestamp') - $time;
    
    // Just now (less than 1 minute)
    if ($diff < 60) {
        return 'Just now';
    }
    
    // Minutes
    if ($diff < 3600) {
        return floor($diff / 60) . 'm';
    }
    
    // Hours
    if ($diff < 86400) {
        return floor($diff / 3600) . 'h';
    }
    
    // Days
    if ($diff < 604800) {
        return floor($diff / 86400) . 'd';
    }
    
    // Weeks
    if ($diff < 2592000) {
        return floor($diff / 604800) . 'w';
    }
    
    // Months
    if ($diff < 31536000) {
        return floor($diff / 2592000) . 'mo';
    }
    
    // Years
    return floor($diff / 31536000) . 'y';
}