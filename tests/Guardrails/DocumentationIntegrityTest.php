<?php

namespace Tests\Guardrails;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Keeps the documentation system of ADR-0003 navigable: relative links resolve,
 * and every requirement, invariant and work-item ID is defined exactly once and
 * referenced only when it exists.
 */
class DocumentationIntegrityTest extends TestCase
{
    public function test_relative_links_point_to_existing_files(): void
    {
        $broken = [];

        foreach ($this->documents() as $path => $content) {
            preg_match_all('/\[[^\]]*\]\(([^)\s]+)\)/', $this->withoutCode($content), $links);

            foreach ($links[1] as $target) {
                if (preg_match('#^(?:[a-z]+:|\#)#i', $target)) {
                    continue;
                }

                $file = urldecode(explode('#', $target)[0]);
                if (! file_exists(dirname($this->absolute($path)).'/'.$file)) {
                    $broken[] = "{$path} → {$target}";
                }
            }
        }

        $this->assertSame([], $broken, "Broken relative links:\n".implode("\n", $broken));
    }

    public function test_requirement_ids_are_defined_once_and_references_exist(): void
    {
        preg_match_all('/^###\s+((?:FR|BR|NFR)-[A-Z]+-\d+)\b/m', $this->read('docs/requirements.md'), $definitions);

        $this->assertDefinedOnceAndReferenced('requirement', $definitions[1], '/\b(?:FR|BR|NFR)-[A-Z]+-\d+\b/');
    }

    public function test_invariant_ids_are_defined_once_and_references_exist(): void
    {
        preg_match_all('/^\|\s*(INV-[A-Z]+-\d+)\s*\|/m', $this->read('docs/domains/README.md'), $definitions);

        $this->assertDefinedOnceAndReferenced('invariant', $definitions[1], '/\bINV-[A-Z]+-\d+\b/');
    }

    public function test_work_items_have_unique_numbers_matching_their_front_matter(): void
    {
        $numbers = [];
        $mismatched = [];

        foreach (glob($this->absolute('docs/work/{,done/}BQ-*.md'), GLOB_BRACE) ?: [] as $file) {
            preg_match('/^(BQ-\d+)-/', basename($file), $fromName);
            preg_match('/^id:\s*(BQ-\d+)\s*$/m', (string) file_get_contents($file), $fromFrontMatter);

            $numbers[] = $fromName[1];
            if (($fromFrontMatter[1] ?? null) !== $fromName[1]) {
                $mismatched[] = basename($file).' has id '.($fromFrontMatter[1] ?? 'none');
            }
        }

        $this->assertSame([], $mismatched, "Work items whose front-matter id differs from the file name:\n".implode("\n", $mismatched));
        $this->assertDefinedOnceAndReferenced('work item', $numbers, '/\bBQ-\d{3}\b/');
    }

    /**
     * @param  list<string>  $definitions
     */
    private function assertDefinedOnceAndReferenced(string $kind, array $definitions, string $referencePattern): void
    {
        $duplicates = array_keys(array_filter(array_count_values($definitions), fn (int $count): bool => $count > 1));
        $this->assertSame([], $duplicates, "Duplicate {$kind} IDs:\n".implode("\n", $duplicates));

        $defined = array_flip($definitions);
        $dangling = [];

        foreach ($this->documents() as $path => $content) {
            preg_match_all($referencePattern, $content, $references);
            foreach (array_unique($references[0]) as $id) {
                if (! isset($defined[$id])) {
                    $dangling[] = "{$path} → {$id}";
                }
            }
        }

        $this->assertSame([], $dangling, "References to undefined {$kind} IDs:\n".implode("\n", $dangling));
    }

    /**
     * Markdown sources of the documentation system, keyed by repository path.
     *
     * @return array<string, string>
     */
    private function documents(): array
    {
        $documents = [];

        foreach (['AGENTS.md', 'README.md'] as $path) {
            $documents[$path] = $this->read($path);
        }

        foreach (['docs', '.ai/rules'] as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->absolute($directory), RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file->getExtension() === 'md' && $file->getFilename() !== '_TEMPLATE.md') {
                    $path = str_replace('\\', '/', substr($file->getPathname(), strlen($this->absolute(''))));
                    $documents[$path] = (string) file_get_contents($file->getPathname());
                }
            }
        }

        ksort($documents);

        return $documents;
    }

    private function withoutCode(string $markdown): string
    {
        return (string) preg_replace(['/```.*?```/s', '/`[^`\n]*`/'], '', $markdown);
    }

    private function read(string $path): string
    {
        return (string) file_get_contents($this->absolute($path));
    }

    private function absolute(string $path): string
    {
        return dirname(__DIR__, 2).'/'.$path;
    }
}
