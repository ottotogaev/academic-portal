<?php
/**
 * @var list<array<string,mixed>> $rows
 * @var ?array{0:string,1:string} $flash
 * @var string $zone
 */
?>
<section class="admin-card admin-card--wide">
<div class="admin-bar"><h1 class="admin-card__title"><?= e(t('admin.feedback.title')) ?></h1></div>
<?php if ($flash !== null): ?>
<p class="<?= $flash[0] === 'ok' ? 'admin-ok' : 'admin-alert' ?>" role="status"><?= e($flash[1]) ?></p>
<?php endif; ?>
<?php if ($rows === []): ?>
<p class="admin-card__text admin-card__text--muted"><?= e(t('admin.feedback.empty')) ?></p>
<?php else: ?>
<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr>
<th><?= e(t('admin.feedback.from')) ?></th>
<th><?= e(t('admin.feedback.subject')) ?></th>
<th><?= e(t('admin.fields.status')) ?></th>
<th><?= e(t('admin.feedback.date')) ?></th>
</tr></thead>
<tbody>
<?php foreach ($rows as $row): ?>
<tr<?= $row['status'] === 'new' ? ' class="is-new"' : '' ?>>
<td><a class="admin-link" href="/admin/feedback/<?= (int) $row['id'] ?>"><?= e((string) $row['sender_name']) ?></a><br><span class="admin-card__text--muted"><?= e((string) $row['sender_email']) ?></span></td>
<td><?= e((string) ($row['subject'] ?? '')) ?: '—' ?></td>
<td><span class="status status--<?= e((string) $row['status']) ?>"><?= e(t('admin.feedback.status.' . $row['status'])) ?></span></td>
<td><?= e(\App\Core\Time::format((string) $row['created_at'], $zone, 'd.m.Y H:i')) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</section>
