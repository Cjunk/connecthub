<?php
/**
 * Page Header Component
 * Reusable component for consistent page headers across the application
 *
 * @param string $title - Main page title
 * @param string $subtitle - Page description/subtitle
 * @param string $icon - Optional icon class (e.g., 'fas fa-users')
 * @param string $buttonText - Button text (optional)
 * @param string $buttonLink - Button link URL (optional)
 * @param string $buttonClass - Button CSS classes (optional, defaults to 'btn btn-primary')
 */

// Extract parameters with defaults
$title = $title ?? '';
$subtitle = $subtitle ?? '';
$icon = $icon ?? '';
$buttonText = $buttonText ?? '';
$buttonLink = $buttonLink ?? '';
$buttonClass = $buttonClass ?? 'btn btn-primary';
?>

<div class="row mb-4">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h1 class="mb-1">
        <?php if ($icon): ?>
          <i class="<?= htmlspecialchars($icon) ?> me-2 text-primary"></i>
        <?php endif; ?>
        <?= htmlspecialchars($title) ?>
      </h1>
      <?php if ($subtitle): ?>
        <p class="text-muted mb-0"><?= htmlspecialchars($subtitle) ?></p>
      <?php endif; ?>
    </div>
    <?php if ($buttonText && $buttonLink): ?>
      <a href="<?= htmlspecialchars($buttonLink) ?>" class="<?= htmlspecialchars($buttonClass) ?>">
        <?= $buttonText ?>
      </a>
    <?php endif; ?>
  </div>
</div>