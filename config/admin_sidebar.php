<?php

return [
    [
        'label' => 'Dashboard',
        'icon' => 'mdi mdi-speedometer',
        'route' => 'dashboard.index',
        'roles' => ['admin', 'manager', 'cashier', 'courier'],
    ],
    [
        'label' => 'Shipments',
        'icon' => 'mdi mdi-truck-delivery',
        'route' => 'shipments.index',
        'roles' => ['admin', 'manager', 'cashier', 'courier'],
    ],
    [
        'label' => 'Shipment Items',
        'icon' => 'mdi mdi-package-variant-closed',
        'route' => 'shipment-items.index',
        'roles' => ['admin', 'manager', 'cashier', 'courier'],
    ],
    [
        'label' => 'Trackings',
        'icon' => 'mdi mdi-map-marker-path',
        'route' => 'shipment-trackings.index',
        'roles' => ['admin', 'manager', 'cashier', 'courier'],
    ],
    [
        'label' => 'Courier Tasks',
        'icon' => 'mdi mdi-clipboard-check-outline',
        'route' => 'courier.tasks',
        'roles' => ['admin', 'manager', 'courier'],
    ],
    [
        'label' => 'Payments',
        'icon' => 'mdi mdi-cash-multiple',
        'route' => 'payments.index',
        'roles' => ['admin', 'manager', 'cashier', 'courier'],
    ],
    [
        'label' => 'Payment Verification',
        'icon' => 'mdi mdi-cash-check',
        'route' => 'payments.verification',
        'roles' => ['admin', 'manager', 'cashier'],
    ],
    [
        'label' => 'Manager Reports',
        'icon' => 'mdi mdi-chart-box',
        'route' => 'manager.reports',
        'roles' => ['admin', 'manager'],
    ],
    [
        'label' => 'Branches',
        'icon' => 'mdi mdi-office-building',
        'route' => 'branches.index',
        'roles' => ['admin'],
    ],
    [
        'label' => 'Rates',
        'icon' => 'mdi mdi-currency-usd',
        'route' => 'rates.index',
        'roles' => ['admin'],
    ],
    [
        'label' => 'Vehicles',
        'icon' => 'mdi mdi-car',
        'route' => 'vehicles.index',
        'roles' => ['admin'],
    ],
    [
        'label' => 'Customers',
        'icon' => 'mdi mdi-account-box-multiple',
        'route' => 'customers.index',
        'roles' => ['admin'],
    ],
    [
        'label' => 'Users',
        'icon' => 'mdi mdi-account-group',
        'route' => 'users.index',
        'roles' => ['admin'],
    ],
];
