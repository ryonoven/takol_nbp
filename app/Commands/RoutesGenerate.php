<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use ReflectionClass;
use ReflectionMethod;

class RoutesGenerate extends BaseCommand
{
    protected $group = 'Custom';
    protected $name = 'routes:generate';
    protected $description = 'Generate route ringkas: halaman dijaga filter nav, function bebas';

    // Controller tanpa filter apa pun (login/logout)
    private array $publicControllers = ['Login', 'Auth'];

    // Controller yang wajib role tertentu
    private array $roleControllers = ['Admin' => 'admin'];

    // Controller yang halamannya boleh dibuka lewat URL langsung (pintu masuk)
    private array $entryControllers = ['Home'];

    // Method yang dianggap "halaman" (dijaga filter nav). Selain ini = function (bebas)
    private array $pageMethods = ['index', 'detail'];
    private array $skipClasses = ['BaseController'];
    private array $skipDirs = ['fpdf', 'makefont', 'font'];

    public function run(array $params)
    {
        $base = str_replace('\\', '/', APPPATH . 'Controllers');
        $buckets = [];
        $tail = [];
        $skipped = [];

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            $rel = substr($path, strlen($base) + 1);

            foreach ($this->skipDirs as $d) {
                if (stripos('/' . $rel, '/' . $d . '/') !== false) {
                    continue 2;
                }
            }

            $src = file_get_contents($path);
            if (
                !preg_match('/^namespace\s+(App\\\\Controllers[\w\\\\]*)\s*;/m', $src, $ns)
                || !preg_match('/^(?:final\s+)?class\s+(\w+)\s+extends\s+[\w\\\\]+/m', $src, $cm)
            ) {
                $skipped[] = $rel;
                continue;
            }

            $short = $cm[1];
            $class = $ns[1] . '\\' . $short;

            if (in_array($short, $this->skipClasses, true) || !class_exists($class)) {
                continue;
            }

            $ref = new ReflectionClass($class);
            if ($ref->isAbstract()) {
                continue;
            }

            $handler = substr($class, strlen('App\\Controllers\\'));
            $subParts = explode('\\', $handler);
            array_pop($subParts);
            $prefix = $subParts ? strtolower(implode('/', $subParts)) . '/' : '';
            $uri = $prefix . strtolower($short);
            $pages = [];
            $funcs = [];
            $indexReq = 0;
            $indexTotal = -1;

            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
                if (
                    $m->class !== $class || $m->isStatic() || $m->isAbstract()
                    || strpos($m->name, '_') === 0 || $m->name === 'initController'
                ) {
                    continue;
                }

                $isPage = in_array(strtolower($m->name), $this->pageMethods, true);
                $r = $m->getNumberOfRequiredParameters();
                $t = $m->getNumberOfParameters();

                for ($k = $r; $k <= $t; $k++) {
                    if ($isPage) {
                        $pages[$k][] = $m->name;
                    } else {
                        $funcs[$k][] = $m->name;
                    }
                }

                if (strtolower($m->name) === 'index') {
                    $indexReq = $r;
                    $indexTotal = $t;
                }
            }

            if (!$pages && !$funcs) {
                continue;
            }

            $pageLines = [];
            $funcLines = [];
            $tailLines = [];

            // uri -> index
            if (isset($pages[0]) && in_array('index', $pages[0], true)) {
                $pageLines[] = sprintf(
                    "    \$routes->match(['get', 'post'], '(?i)%s', '%s::index');",
                    $uri,
                    $handler
                );
            }

            $this->emit($pageLines, $uri, $handler, $pages);
            $this->emit($funcLines, $uri, $handler, $funcs);
            
            for ($k = max(1, $indexReq); $k <= $indexTotal; $k++) {
                $dest = 'index';
                for ($i = 1; $i <= $k; $i++) {
                    $dest .= '/$' . $i;
                }
                $tailLines[] = sprintf(
                    "    \$routes->match(['get', 'post'], '(?i)%s%s', '%s::%s');",
                    $uri,
                    str_repeat('/(:segment)', $k),
                    $handler,
                    $dest
                );
            }

            $pageKey = $this->filterFor($short, true);
            $funcKey = $this->filterFor($short, false);

            if ($pageLines) {
                $buckets[$pageKey][] = "    // {$short} (halaman)\n" . implode("\n", $pageLines);
            }
            if ($funcLines) {
                $buckets[$funcKey][] = "    // {$short} (function)\n" . implode("\n", $funcLines);
            }
            if ($tailLines) {
                $tail[$pageKey][] = "    // {$short} (halaman dengan parameter)\n" . implode("\n", $tailLines);
            }
        }

        $out = "<?php\n// AUTO-GENERATED oleh `php spark routes:generate`. Jangan diedit manual.\n\n";
        $total = 0;

        $write = function (array $groups) use (&$out, &$total) {
            foreach ($groups as $key => $blocks) {
                $opt = $key === '' ? '' : "['filter' => '" . $key . "'], ";
                $out .= "\$routes->group('', {$opt}static function (\$routes) {\n"
                    . implode("\n\n", $blocks) . "\n});\n\n";
                $total += substr_count(implode('', $blocks), '$routes->match');
            }
        };
        $write($buckets);
        $write($tail);

        file_put_contents(APPPATH . 'Config/RoutesGenerated.php', $out);

        CLI::write("Selesai: app/Config/RoutesGenerated.php ({$total} route)", 'green');
        if ($skipped) {
            CLI::write('File dilewati (bukan controller standar):', 'yellow');
            foreach ($skipped as $s) {
                CLI::write('  - ' . $s);
            }
        }
    }

    // private function filters(string $short, bool $page): array
    // {
    //     if (in_array($short, $this->publicControllers, true)) {
    //         return [];
    //     }

    //     $f = ['login'];
    //     if (isset($this->roleControllers[$short])) {
    //         $f[] = 'role:' . $this->roleControllers[$short];
    //     }
    //     if ($page && ! in_array($short, $this->entryControllers, true)) {
    //         $f[] = 'nav';
    //     }

    //     return $f;
    // }

    private function filterFor(string $short, bool $page): string
    {
        if (in_array($short, $this->publicControllers, true)) {
            return '';
        }

        $args = [];
        if (isset($this->roleControllers[$short])) {
            $args[] = $this->roleControllers[$short];
        }
        if ($page && !in_array($short, $this->entryControllers, true)) {
            $args[] = 'nav';
        }

        return $args ? 'guard:' . implode(',', $args) : 'login';
    }

    private function emit(array &$lines, string $uri, string $handler, array $byK): void
    {
        ksort($byK);
        foreach ($byK as $k => $names) {
            $names = array_values(array_unique($names));
            sort($names);

            $dest = '$1';
            for ($i = 2; $i <= $k + 1; $i++) {
                $dest .= '/$' . $i;
            }

            $lines[] = sprintf(
                "    \$routes->match(['get', 'post'], '(?i)%s/(%s)%s', '%s::%s');",
                $uri,
                implode('|', $names),
                str_repeat('/(:segment)', $k),
                $handler,
                $dest
            );
        }
    }
}