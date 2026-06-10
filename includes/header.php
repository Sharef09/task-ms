<?php
/**
 * Header Include
 * Outputs HTML head opening, meta tags, and CSS/JS CDN links.
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<meta name="csrf-token" content="<?= csrf_token() ?>">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Crect width='16' height='16' rx='3' fill='%232563eb'/%3E%3Cpath d='M5 3.5h4l3 3v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1z' fill='white'/%3E%3Cpath d='M9 3.5V6h2.5' fill='none' stroke='%232563eb' stroke-width='0.8'/%3E%3Cpath d='M6.5 8.5l-.3 1.3 1.3-.3L11 6l-1-1z' fill='none' stroke='%232563eb' stroke-width='0.8' stroke-linejoin='round'/%3E%3C/svg%3E">

<!-- Bootstrap 5.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

<!-- Font Awesome 6.4 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

<!-- Chart.js 4 -->
<script src="<?= asset('js/chart.min.js') ?>"></script>

<!-- Custom Styles -->
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
