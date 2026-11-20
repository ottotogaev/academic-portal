<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\App;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Time;
use App\Services\ContentForm;
use App\Services\ContentRepository;

/** Generic list / create / edit / delete for one content type from config/content.php. */
final class ContentController extends AdminController
{
    private ContentRepository $repo;

    /** @var array<string,mixed> */
    private array $def;

    public function __construct(App $app, private string $type)
    {
        parent::__construct($app);
        $this->def = (array) ($app->config()->get('content.' . $type) ?? throw new \LogicException('Unknown content type.'));
        $this->repo = $app->content($type);
    }

    public function index(Request $request): Response
    {
        return $this->page('admin/content/list', [
            'title' => t('admin.types.' . $this->type),
            'type' => $this->type,
            'rows' => $this->repo->all(),
            'flash' => $this->app->session()->pull('flash'),
            'zone' => $this->zone(),
            'dated' => $this->def['dated'] ?? null,
        ]);
    }

    public function create(Request $request): Response
    {
        $old = ['status' => 'draft'];

        return $this->form(null, $old, [], 200);
    }

    public function store(Request $request): Response
    {
        return $this->persist($request, null);
    }

    public function edit(Request $request): Response
    {
        $id = $this->id($request);
        $item = $this->repo->find($id) ?? throw new HttpException(404);

        $old = ['status' => (string) $item['base']['status']];
        foreach ((array) $this->def['base'] as $name => $spec) {
            $value = $item['base'][$name] ?? null;
            $old[$name] = $spec['type'] === 'datetime'
                ? Time::utcToInput($value === null ? null : (string) $value, $this->zone())
                : ($value === null ? '' : (string) $value);
        }
        foreach ($item['translations'] as $locale => $row) {
            foreach (array_keys((array) $this->def['fields']) as $field) {
                $old[$locale . '__' . $field] = (string) ($row[$field] ?? '');
            }
        }

        return $this->form($id, $old, [], 200);
    }

    public function update(Request $request): Response
    {
        $id = $this->id($request);
        $this->repo->find($id) ?? throw new HttpException(404);

        return $this->persist($request, $id);
    }

    public function destroy(Request $request): Response
    {
        $id = $this->id($request);
        $stored = !empty($this->def['upload']) ? (string) ($this->repo->find($id)['base']['stored_name'] ?? '') : '';
        try {
            if (!$this->repo->delete($id)) {
                throw new HttpException(404);
            }
            if ($stored !== '') {
                $this->app->uploads()->remove($stored);
            }
            $this->app->session()->set('flash', ['ok', t('admin.messages.deleted')]);
        } catch (\PDOException $e) {
            // Still referenced (a topic with assignments, lessons or materials): keep it, explain why.
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }
            $this->app->session()->set('flash', ['error', t('admin.messages.in_use')]);
        }

        return $this->redirect('/admin/' . $this->type);
    }

    private function persist(Request $request, ?int $id): Response
    {
        $result = ContentForm::validate(
            $this->def,
            $request,
            $this->locales(),
            $this->zone(),
            $this->refs(),
            $this->repo,
            $id,
        );

        // File (materials): required on create, optional replacement on edit. Checked before anything is stored.
        $upload = null;
        $file = null;
        if (!empty($this->def['upload'])) {
            $file = $request->file('file');
            $upload = $this->app->uploads()->inspect($file);
            if ($upload['error'] !== null) {
                $result['errors']['file'] = $upload['error'];
            } elseif ($upload['empty'] && $id === null) {
                $result['errors']['file'] = 'file_required';
            }
        }

        if ($result['errors'] !== []) {
            return $this->form($id, $result['old'], $result['errors'], 422);
        }

        $base = $result['base'];
        $nowUtc = Time::nowUtc();
        if (array_key_exists('slug', $base) && $base['slug'] === null) {
            $base['slug'] = $this->repo->uniqueSlug((string) $result['translations'][$this->locales()[0]]['title'], $id);
        }
        // Publishing without a date means "now"; a published item is never undated.
        if (array_key_exists('published_at', $base) && $base['status'] === 'published' && $base['published_at'] === null) {
            $base['published_at'] = $nowUtc;
        }

        $storage = $this->app->uploads();
        $newFile = null;
        $oldFile = null;
        if ($upload !== null && $upload['meta'] !== null && $file !== null) {
            $newFile = $storage->store($file, $upload['meta']['ext']);
            $base['stored_name'] = $newFile;
            $base['original_filename'] = $upload['meta']['original'];
            $base['mime_type'] = $upload['meta']['mime'];
            $base['file_size'] = $upload['meta']['size'];
            if ($id !== null) {
                $oldFile = (string) (($this->repo->find($id)['base']['stored_name'] ?? ''));
            }
        }

        try {
            $id = $this->repo->save($id, $base, $result['translations'], $nowUtc);
        } catch (\Throwable $e) {
            if ($newFile !== null) {
                $storage->remove($newFile); // never leave an orphan behind a failed save
            }
            throw $e;
        }
        if ($oldFile !== null && $oldFile !== '') {
            $storage->remove($oldFile);
        }
        $this->app->session()->set('flash', ['ok', t('admin.messages.saved')]);

        return $this->redirect('/admin/' . $this->type . '/' . $id . '/edit');
    }

    /** @param array<string,string> $old @param array<string,string> $errors */
    private function form(?int $id, array $old, array $errors, int $status): Response
    {
        return $this->page('admin/content/form', [
            'title' => t($id === null ? 'admin.form.new' : 'admin.form.edit') . ': ' . t('admin.types.' . $this->type),
            'type' => $this->type,
            'def' => $this->def,
            'id' => $id,
            'old' => $old,
            'errors' => $errors,
            'locales' => $this->locales(),
            'refs' => $this->refs(),
            'upload' => !empty($this->def['upload']) ? [
                'limit' => $this->app->uploads()->effectiveLimit(),
                'extensions' => $this->app->uploads()->extensions(),
                'current' => $id === null ? null : ($this->repo->find($id)['base'] ?? null),
            ] : null,
            'flash' => $this->app->session()->pull('flash'),
        ], $status);
    }

    private function id(Request $request): int
    {
        $raw = (string) $request->param('id');
        if (preg_match('/^\d{1,10}$/', $raw) !== 1) {
            throw new HttpException(404);
        }

        return (int) $raw;
    }

    /** @return list<string> default locale first, then the other locales that have translated UI */
    private function locales(): array
    {
        $default = $this->app->config()->get('app.default_locale', 'uz');
        $locales = [(string) $default];
        foreach ((array) $this->app->config()->get('app.locales', []) as $locale) {
            if ($locale !== $default && $this->app->translator()->available((string) $locale)) {
                $locales[] = (string) $locale;
            }
        }

        return $locales;
    }

    /** @return array<string,array<int,string>> id => title for every type this form can reference */
    private function refs(): array
    {
        $refs = [];
        foreach ((array) $this->def['base'] as $spec) {
            if ($spec['type'] === 'ref' && !isset($refs[$spec['ref']])) {
                $refs[$spec['ref']] = $this->app->content((string) $spec['ref'])->titles();
            }
        }

        return $refs;
    }

    private function zone(): string
    {
        return (string) $this->app->config()->get('app.timezone', 'Asia/Tashkent');
    }
}
