<?php
// worker-page-start.php — call at top of every page after auth
// Usage: include('../includes/worker-page-start.php');
// Requires: $worker, $worker_id, $pageTitle, $pageSubtitle (optional) to be set
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo wE($pageTitle ?? 'Worker Portal'); ?> — HireX</title>
    <meta name="description" content="HireX Worker Portal — <?php echo wE($pageTitle ?? 'Worker Portal'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style><?php include __DIR__ . '/worker-styles.css.php'; ?></style>
    <?php if (!empty($extraCSS)) echo "<style>{$extraCSS}</style>"; ?>
</head>
<body>
<div class="overlay" id="overlay" onclick="wToggleSidebar()"></div>
<?php include __DIR__ . '/worker-sidebar.php'; ?>
<main class="main-content" id="mainContent">
<?php include __DIR__ . '/worker-header.php'; ?>
<div class="page-title">
    <h1><?php echo wE($pageTitle ?? 'Worker Portal'); ?></h1>
    <?php if (!empty($pageSubtitle)): ?><p><?php echo wE($pageSubtitle); ?></p><?php endif; ?>
</div>
