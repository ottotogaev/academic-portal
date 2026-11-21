<?php
/**
 * @var array<string,mixed> $message
 * @var ?array{0:string,1:string} $flash
 * @var string $zone
 */
$id = (int) $message['id'];
?>
<section class="admin-card admin-card--wide">
<div class="admin-bar">
<h1 class="admin-card__title"><?= e(t('admin.feedback.title')) ?> #<?= $id ?></h1>
<a class="admin-link" href="/admin/feedback"><?= e(t('admin.back')) ?></a>
</div>
<?php if ($flash !== null): ?>
<p class="<?= $flash[0] === 'ok' ? 'admin-ok' : 'admin-alert' ?>" role="status"><?= e($flash[1]) ?></p>
<?php endif; ?>
<dl class="admin-facts">
<div><dt><?= e(t('admin.feedback.from')) ?></dt><dd><?= e((string) $message['sender_name']) ?></dd></div>
<div><dt><?= e(t('admin.feedback.email')) ?></dt><dd><a class="admin-link" href="mailto:<?= e(rawurlencode((string) $message['sender_email'])) ?>"><?= e((string) $message['sender_email']) ?></a></dd></div>
<div><dt><?= e(t('admin.feedback.subject')) ?></dt><dd><?= e((string) ($message['subject'] ?? '')) ?: '—' ?></dd></div>
<div><dt><?= e(t('admin.feedback.date')) ?></dt><dd><?= e(\App\Core\Time::format((string) $message['created_at'], $zone, 'd.m.Y H:i')) ?></dd></div>
</dl>
<div class="admin-message prose"><?= text_html((string) $message['message']) ?></div>
<form method="post" action="/admin/feedback/<?= $id ?>/status" class="admin-inline">
<?= csrf_field() ?>
<label for="status"><?= e(t('admin.fields.status')) ?></label>
<select id="status" name="status">
<?php foreach (\App\Services\FeedbackRepository::STATUSES as $status): ?>
<option value="<?= e($status) ?>"<?= $message['status'] === $status ? ' selected' : '' ?>><?= e(t('admin.feedback.status.' . $status)) ?></option>
<?php endforeach; ?>
</select>
<button class="btn btn--sm" type="submit"><?= e(t('admin.form.save')) ?></button>
</form>
<form method="post" action="/admin/feedback/<?= $id ?>/delete" onsubmit="return confirm(<?= e(json_encode(t('admin.list.confirm_delete'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)">
<?= csrf_field() ?>
<button class="admin-link admin-link--button admin-link--danger" type="submit"><?= e(t('admin.list.delete')) ?></button>
</form>
</section>
