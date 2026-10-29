<?php

namespace Tests\Feature;

use App\Models\User;
use App\Queries\PromotionData;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Klik semuanya": login sebagai setiap akun demo, buka semua link internal yang terlihat,
 * lalu kirim setiap form dengan nilai bawaan halaman. Tidak boleh ada respons 5xx.
 */
class CrawlEverythingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const ACCOUNTS = [
        'admin@talentrank.test', 'hrd@talentrank.test', 'atasan@talentrank.test',
        'direktur@talentrank.test', 'karyawan@talentrank.test',
    ];

    public function test_semua_link_dan_form_tidak_menghasilkan_error_server(): void
    {
        foreach (self::ACCOUNTS as $email) {
            $user = User::where('email', $email)->firstOrFail();
            $this->crawlAs($user);
        }
    }

    private function crawlAs(User $user): void
    {
        $queue = [$user->dashboardUrl()];
        $seen = [];
        $forms = [];

        while ($queue && count($seen) < 60) {
            $url = array_shift($queue);
            $path = parse_url($url, PHP_URL_PATH) . (($q = parse_url($url, PHP_URL_QUERY)) ? "?$q" : '');
            if (isset($seen[$path])) {
                continue;
            }
            $seen[$path] = true;

            PromotionData::flush();
            $response = $this->actingAs($user)->get($path);
            $this->assertLessThan(500, $response->status(), "{$user->email} GET {$path} → {$response->status()}");

            if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
                continue;
            }

            [$links, $pageForms] = $this->parse($response->getContent());
            foreach ($links as $link) {
                if (! isset($seen[$link])) {
                    $queue[] = $link;
                }
            }
            foreach ($pageForms as $form) {
                $forms[$form['method'] . ' ' . $form['action'] . json_encode($form['data'])] = $form + ['from' => $path];
            }
        }

        foreach ($forms as $form) {
            PromotionData::flush();
            $response = $this->actingAs($user)->from($form['from'])->call($form['method'], $form['action'], $form['data']);
            $this->assertLessThan(500, $response->status(),
                "{$user->email} {$form['method']} {$form['action']} (dari {$form['from']}) → {$response->status()}");
        }
    }

    /** @return array{0: list<string>, 1: list<array{method:string, action:string, data:array}>} */
    private function parse(string $html): array
    {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $links = [];
        foreach ($xpath->query('//a[@href]') as $a) {
            $href = $a->getAttribute('href');
            $path = parse_url($href, PHP_URL_PATH);
            $host = parse_url($href, PHP_URL_HOST);
            if (! $path || ($host && $host !== parse_url(config('app.url'), PHP_URL_HOST) && $host !== 'localhost') || str_starts_with($href, '#')) {
                continue;
            }
            $links[] = $path . (($q = parse_url($href, PHP_URL_QUERY)) ? "?$q" : '');
        }

        $forms = [];
        foreach ($xpath->query('//form[@action]') as $form) {
            $action = parse_url($form->getAttribute('action'), PHP_URL_PATH);
            if (! $action || $action === '/logout') {
                continue; // logout diuji terpisah; di sini akan memutus crawl
            }

            $data = [];
            foreach ($xpath->query('.//input[@name] | .//textarea[@name] | .//select[@name]', $form) as $field) {
                $name = $field->getAttribute('name');
                $type = strtolower($field->getAttribute('type'));
                if (in_array($type, ['checkbox', 'radio'], true) && ! $field->hasAttribute('checked')) {
                    continue;
                }
                $value = match ($field->nodeName) {
                    'textarea' => $field->textContent,
                    'select' => ($opt = $xpath->query('.//option[@selected]', $field)->item(0) ?? $xpath->query('.//option', $field)->item(0)) ? $opt->getAttribute('value') : '',
                    default => $field->getAttribute('value'),
                };
                $data = array_merge_recursive($data, $this->nest($name, $value));
            }

            $method = strtoupper($data['_method'] ?? $form->getAttribute('method') ?: 'GET');
            unset($data['_method'], $data['_token']);

            // Tombol submit bernama (mis. action=draft|submit, decision=approve|reject): coba masing-masing.
            $submitters = $xpath->query('.//button[@type="submit"][@name]', $form);
            if ($submitters->length === 0) {
                $forms[] = ['method' => $method, 'action' => $action, 'data' => $data];
            }
            foreach ($submitters as $btn) {
                $forms[] = ['method' => $method, 'action' => $action, 'data' => $data + [$btn->getAttribute('name') => $btn->getAttribute('value')]];
            }
        }

        return [array_values(array_unique($links)), $forms];
    }

    /** "criteria[C1][weight]" → ['criteria' => ['C1' => ['weight' => v]]] */
    private function nest(string $name, string $value): array
    {
        if (! str_contains($name, '[')) {
            return [$name => $value];
        }
        preg_match_all('/[^\[\]]+/', $name, $m);
        $result = $value;
        foreach (array_reverse($m[0]) as $key) {
            $result = [$key => $result];
        }

        return $result;
    }
}
