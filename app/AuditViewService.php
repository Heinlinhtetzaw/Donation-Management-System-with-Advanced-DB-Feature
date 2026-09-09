<?php

function audit_entity_labels() {
    return [
        'donation' => 'Donation',
        'foundation' => 'Foundation',
        'news' => 'News',
    ];
}

function audit_record_label($entityType, $entityId) {
    $labels = audit_entity_labels();
    return ($labels[$entityType] ?? ucfirst((string) $entityType)) . ' #' . $entityId;
}

function audit_record_url($entityType, $entityId, $actionType) {
    if ($actionType === 'deleted') {
        return 'audit_record.php?type=' . rawurlencode((string) $entityType)
            . '&id=' . rawurlencode((string) $entityId);
    }

    $routes = [
        'donation' => 'donation_detail.php',
        'foundation' => 'preview_foundation.php',
        'news' => 'preview_news.php',
    ];
    if (!isset($routes[$entityType])) {
        return null;
    }

    return $routes[$entityType] . '?id=' . rawurlencode((string) $entityId);
}
