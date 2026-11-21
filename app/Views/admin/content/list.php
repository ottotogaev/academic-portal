<?php
/**
 * @var string               $type
 * @var list<array>          $rows
 * @var ?array{0:string,1:string} $flash
 * @var string               $zone
 * @var ?string              $dated  base column shown as the date, or null for "updated"
 */
$dateColumn = $dated ?? 'updated_at';
?>
<section class="admin-card admin-card--wide">
<div class="admin-bar">
<h1 class="admin-card__title"><?= e(t('admin.types.' . $type)) ?></h1>
<a class="btn btn--sm" href="/admin/<?= e($type) ?>/new"><?= e(t('admin.list.new')) ?></a>
</div>
<?php if ($flash !== null): ?>
<p class="<?= $flash[0] === 'ok' ? 'admin-ok' : 'admin-alert' ?>" role="status"><?= e($flash[1]) ?></p>
<?php endif; ?>
<?php if ($rows === []): ?>
<p class="admin-card__text admin-card__text--muted"><?= e(t('admin.list.empty')) ?></p>
<?php else: ?>
<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr>
<th><?= e(t('admin.fields.title')) ?></th>
<th><?= e(t('admin.fields.status')) ?></th>
<th><?= e(t($dated !== null ? 'admin.fields.' . $dated : 'admin.list.updated')) ?></th>
<th></th>
</tr></thead>
<tbody>
<?php foreach ($rows as $row): ?>
<tr>
<td><a class="admin-link" href="/admin/<?= e($type) ?>/<?= (int) $row['id'] ?>/edit"><?= e((string) ($row['title'] ?? '—')) ?></a></td>
<td><span class="status status--<?= e((string) $row['status']) ?>"><?= e(t('admin.status.' . $row['status'])) ?></span></td>
<td><?= e(\App\Core\Time::format(isset($row[$dateColumn]) ? (string) $row[$dateColumn] : null, $zone, 'd.m.Y H:i')) ?: '—' ?></td>
<td><div class="admin-table__actions">
<a class="admin-link" href="/admin/<?= e($type) ?>/<?= (int) $row['id'] ?>/edit"><?= e(t('admin.list.edit')) ?></a>
<form method="post" action="/admin/<?= e($type) ?>/<?= (int) $row['id'] ?>/delete" data-confirm="<?= e(t('admin.list.confirm_delete')) ?>">
<?= csrf_field() ?>
<button class="admin-link admin-link--button admin-link--danger" type="submit"><?= e(t('admin.list.delete')) ?></button>
</form>
</div></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</section>
