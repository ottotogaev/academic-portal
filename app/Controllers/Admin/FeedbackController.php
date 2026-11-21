<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Time;
use App\Services\FeedbackRepository;

/** Inbox for contact-form messages. Messages are plain text and always escaped on output. */
final class FeedbackController extends AdminController
{
    public function index(Request $request): Response
    {
        return $this->page('admin/feedback/list', [
            'title' => t('admin.feedback.title'),
            'rows' => $this->app->feedback()->all(),
            'flash' => $this->app->session()->pull('flash'),
            'zone' => $this->zone(),
        ]);
    }

    public function show(Request $request): Response
    {
        $message = $this->app->feedback()->find($this->id($request)) ?? throw new HttpException(404);

        return $this->page('admin/feedback/show', [
            'title' => t('admin.feedback.title'),
            'message' => $message,
            'flash' => $this->app->session()->pull('flash'),
            'zone' => $this->zone(),
        ]);
    }

    public function status(Request $request): Response
    {
        $id = $this->id($request);
        $status = $request->input('status');
        if (!in_array($status, FeedbackRepository::STATUSES, true)) {
            throw new HttpException(400);
        }
        if (!$this->app->feedback()->setStatus($id, $status, Time::nowUtc())) {
            throw new HttpException(404);
        }
        $this->app->session()->set('flash', ['ok', t('admin.messages.saved')]);

        return $this->redirect('/admin/feedback/' . $id);
    }

    public function destroy(Request $request): Response
    {
        if (!$this->app->feedback()->delete($this->id($request))) {
            throw new HttpException(404);
        }
        $this->app->session()->set('flash', ['ok', t('admin.messages.deleted')]);

        return $this->redirect('/admin/feedback');
    }

    private function id(Request $request): int
    {
        $raw = (string) $request->param('id');
        if (preg_match('/^\d{1,10}$/', $raw) !== 1) {
            throw new HttpException(404);
        }

        return (int) $raw;
    }

    private function zone(): string
    {
        return (string) $this->app->config()->get('app.timezone', 'Asia/Tashkent');
    }
}
