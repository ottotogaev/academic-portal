<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

final class AuthController extends AdminController
{
    public function showLogin(Request $request): Response
    {
        if ($this->app->auth()->check()) {
            return $this->redirect('/admin/dashboard');
        }

        return $this->page('admin/login', ['title' => t('admin.login.title'), 'error' => null, 'username' => '']);
    }

    public function login(Request $request): Response
    {
        $auth = $this->app->auth();
        $username = mb_substr(trim($request->input('username')), 0, 50);

        if ($auth->throttled($request->ip)) {
            $this->app->logger()->warning('Admin login throttled');

            return $this->page('admin/login', [
                'title' => t('admin.login.title'),
                'error' => t('admin.login.throttled'),
                'username' => $username,
            ], 429)->withHeader('Retry-After', (string) Auth::WINDOW_SECONDS);
        }

        if (!$auth->attempt($username, $request->input('password'), $request->ip)) {
            $this->app->logger()->warning('Admin login failed');

            // One message for unknown user and wrong password.
            return $this->page('admin/login', [
                'title' => t('admin.login.title'),
                'error' => t('admin.login.invalid'),
                'username' => $username,
            ], 422);
        }

        return $this->redirect('/admin/dashboard');
    }

    public function logout(Request $request): Response
    {
        $this->app->auth()->logout();

        return $this->redirect('/admin/login');
    }

    public function showPassword(Request $request): Response
    {
        return $this->page('admin/password', ['title' => t('admin.password.title'), 'error' => null, 'done' => false]);
    }

    public function changePassword(Request $request): Response
    {
        $new = $request->input('new_password');
        $error = null;

        if ($new !== $request->input('new_password_confirm')) {
            $error = t('admin.password.mismatch');
        } else {
            $result = $this->app->auth()->changePassword($request->input('current_password'), $new);
            $error = match ($result) {
                null => null,
                'current' => t('admin.password.wrong_current'),
                'weak' => t('admin.password.weak', ['min' => (string) Auth::MIN_PASSWORD_LENGTH]),
                default => t('admin.password.failed'),
            };
        }

        return $this->page('admin/password', [
            'title' => t('admin.password.title'),
            'error' => $error,
            'done' => $error === null,
        ], $error === null ? 200 : 422);
    }
}
