<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Time;
use App\Services\ProfileForm;
use App\Services\ProfileRepository;

/** Edits the single teacher profile shown on the About and Contact pages. */
final class ProfileController extends AdminController
{
    public function show(Request $request): Response
    {
        return $this->form($this->currentValues(), [], 200);
    }

    public function save(Request $request): Response
    {
        $result = ProfileForm::validate($request, $this->locales());
        if ($result['errors'] !== []) {
            return $this->form($result['old'], $result['errors'], 422);
        }
        $this->app->profileRepository()->save($result['base'], $result['translations'], Time::nowUtc());
        $this->app->session()->set('flash', ['ok', t('admin.messages.saved')]);

        return $this->redirect('/admin/profile');
    }

    /** @param array<string,string> $old @param array<string,string> $errors */
    private function form(array $old, array $errors, int $status): Response
    {
        return $this->page('admin/profile', [
            'title' => t('admin.profile.title'),
            'old' => $old,
            'errors' => $errors,
            'locales' => $this->locales(),
            'fields' => ProfileRepository::FIELDS,
            'flash' => $this->app->session()->pull('flash'),
        ], $status);
    }

    /**
     * Saved values, or the confirmed data from the language file before the first save, so saving the form
     * for the first time keeps what the public pages already show.
     *
     * @return array<string,string>
     */
    private function currentValues(): array
    {
        $row = $this->app->profileRepository()->get();
        $old = ['email' => (string) ($row['email'] ?? ''), 'phone' => (string) ($row['phone'] ?? '')];
        foreach ($this->locales() as $locale) {
            $t = $row['translations'][$locale] ?? null;
            foreach (ProfileRepository::FIELDS as $field) {
                if ($t !== null) {
                    $old[$locale . '__' . $field] = (string) ($t[$field] ?? '');
                } elseif ($row === null) {
                    $key = ['full_name' => 'full_name', 'position' => 'position', 'academic_degree' => 'degree', 'specialization' => 'specialization'][$field] ?? null;
                    $old[$locale . '__' . $field] = $key === null ? '' : $this->app->translator()->get($locale, 'profile.' . $key);
                } else {
                    $old[$locale . '__' . $field] = '';
                }
            }
        }

        return $old;
    }

    /** @return list<string> */
    private function locales(): array
    {
        $default = (string) $this->app->config()->get('app.default_locale', 'uz');
        $locales = [$default];
        foreach ((array) $this->app->config()->get('app.locales', []) as $locale) {
            if ($locale !== $default && $this->app->translator()->available((string) $locale)) {
                $locales[] = (string) $locale;
            }
        }

        return $locales;
    }
}
