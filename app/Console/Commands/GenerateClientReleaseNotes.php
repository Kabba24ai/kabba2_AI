<?php

namespace App\Console\Commands;

use App\Services\OpenAIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;
use Throwable;

#[AsCommand(name: 'docs:new-features', description: 'Generate customer-facing New Features & Improvements notes from git updates.')]
class GenerateClientReleaseNotes extends Command
{
    protected $signature = 'docs:new-features
        {--since= : Natural date for git log (e.g. "2 weeks ago" or "2026-06-01")}
        {--until= : End date for git log (default: now)}
        {--from-tag= : Start git tag/ref (uses --to-ref for range)}
        {--to-ref=HEAD : End git ref for range mode}
        {--max-commits=80 : Maximum commits to include}
        {--output= : Output markdown path (default: docs/new-features-improvements-YYYY-MM-DD.md)}
        {--dry-run : Build source data only, do not call AI}';

    protected $description = 'Create a customer-ready New Features & Improvements markdown document using git history + AI.';

    public function __construct(private readonly OpenAIService $openAIService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $repoRoot = $this->resolveRepoRoot();
            $source = $this->collectSourceData($repoRoot);

            if (empty($source['commits'])) {
                $this->warn('No commits found for the selected range.');

                return self::SUCCESS;
            }

            $outputPath = $this->resolveOutputPath($repoRoot);

            if ($this->option('dry-run')) {
                $this->line('Dry run enabled: AI call skipped.');
                $this->line('Commit count: ' . count($source['commits']));
                $this->line('Changed file count: ' . count($source['files']));
                $this->line('Suggested output path: ' . str_replace('\\', '/', $outputPath));

                return self::SUCCESS;
            }

            $prompt = $this->buildPrompt($source);
            $response = $this->openAIService->chatCompletion([
                ['role' => 'user', 'content' => $prompt],
            ]);
            $aiText = trim($response['choices'][0]['message']['content'] ?? 'No response text returned.');

            $content = $this->buildDocument($source, $aiText);
            File::ensureDirectoryExists(dirname($outputPath));
            File::put($outputPath, $content);

            $this->info('New Features & Improvements doc created.');
            $this->line(str_replace('\\', '/', $outputPath));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Failed to generate release notes: ' . $exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveRepoRoot(): string
    {
        $result = $this->runGit(['rev-parse', '--show-toplevel'], base_path());

        return trim($result);
    }

    private function collectSourceData(string $repoRoot): array
    {
        $maxCommits = max(1, (int) $this->option('max-commits'));
        $fromTag = $this->option('from-tag');
        $toRef = $this->option('to-ref') ?: 'HEAD';
        $since = $this->option('since');
        $until = $this->option('until');

        $logArgs = [
            'log',
            '--no-merges',
            '--date=short',
            '--pretty=format:%H|%ad|%an|%s',
            "--max-count={$maxCommits}",
        ];

        if (!empty($since)) {
            $logArgs[] = '--since=' . $since;
        }

        if (!empty($until)) {
            $logArgs[] = '--until=' . $until;
        }

        if (!empty($fromTag)) {
            $range = $fromTag . '..' . $toRef;
            $logArgs[] = $range;
            $fileArgs = ['diff', '--name-status', $range];
        } else {
            $logArgs[] = $toRef;
            $fileArgs = ['log', '--name-only', '--pretty=format:'];

            if (!empty($since)) {
                $fileArgs[] = '--since=' . $since;
            }

            if (!empty($until)) {
                $fileArgs[] = '--until=' . $until;
            }

            $fileArgs[] = $toRef;
        }

        $logOutput = trim($this->runGit($logArgs, $repoRoot));
        $fileOutput = trim($this->runGit($fileArgs, $repoRoot));

        $commits = $this->parseCommitLines($logOutput);
        $files = $this->parseFiles($fileOutput, !empty($fromTag));

        return [
            'repo_root' => $repoRoot,
            'from_tag' => $fromTag,
            'to_ref' => $toRef,
            'since' => $since,
            'until' => $until,
            'generated_at' => now()->toDateTimeString(),
            'commit_count' => count($commits),
            'file_count' => count($files),
            'commits' => $commits,
            'files' => $files,
        ];
    }

    private function parseCommitLines(string $output): array
    {
        if ($output === '') {
            return [];
        }

        $rows = preg_split('/\r\n|\r|\n/', $output) ?: [];
        $commits = [];

        foreach ($rows as $row) {
            $parts = explode('|', $row, 4);

            if (count($parts) !== 4) {
                continue;
            }

            $commits[] = [
                'hash' => trim($parts[0]),
                'date' => trim($parts[1]),
                'author' => trim($parts[2]),
                'subject' => trim($parts[3]),
            ];
        }

        return $commits;
    }

    private function parseFiles(string $output, bool $isDiffMode): array
    {
        if ($output === '') {
            return [];
        }

        $rows = preg_split('/\r\n|\r|\n/', $output) ?: [];
        $files = [];

        foreach ($rows as $row) {
            $row = trim($row);

            if ($row === '') {
                continue;
            }

            if ($isDiffMode) {
                $parts = preg_split('/\s+/', $row, 2);
                $row = $parts[1] ?? $row;
            }

            $files[$row] = true;
        }

        ksort($files);

        return array_keys($files);
    }

    private function resolveOutputPath(string $repoRoot): string
    {
        $output = $this->option('output');

        if (!empty($output)) {
            if ($this->isAbsolutePath($output)) {
                return $output;
            }

            return $repoRoot . DIRECTORY_SEPARATOR . ltrim($output, '\\/');
        }

        $fileName = 'new-features-improvements-' . now()->format('Y-m-d') . '.md';

        return $repoRoot . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . $fileName;
    }

    private function buildPrompt(array $source): string
    {
        $commitLines = array_map(
            fn(array $commit) => "- [{$commit['date']}] {$commit['subject']} ({$commit['author']}, {$commit['hash']})",
            $source['commits']
        );

        $fileLines = array_map(fn(string $file) => '- ' . $file, array_slice($source['files'], 0, 300));

        $scope = !empty($source['from_tag'])
            ? "Range: {$source['from_tag']}..{$source['to_ref']}"
            : 'Filters: since=' . ($source['since'] ?: 'N/A') . ', until=' . ($source['until'] ?: 'N/A') . ', ref=' . $source['to_ref'];

        return implode("\n", [
            'You are writing a customer-facing release update titled "New Features & Improvements".',
            'Audience: non-technical business users.',
            'Tone: clear, practical, and trustworthy. No hype.',
            '',
            'Requirements:',
            '1. Prioritize customer impact over internal implementation details.',
            '2. If uncertain, classify as "internal improvements".',
            '3. Do not invent features.',
            '4. Keep each bullet concise (1-3 lines).',
            '5. Mention likely user-visible behavior changes under "What to Expect".',
            '',
            'Output EXACTLY these sections in markdown:',
            '## New Features',
            '## Improvements',
            '## Fixes',
            '## What to Expect',
            '## Action Needed',
            '## Short Email Summary',
            '',
            'Release scope:',
            $scope,
            '',
            'Commits:',
            implode("\n", $commitLines),
            '',
            'Changed files:',
            implode("\n", $fileLines),
        ]);
    }

    private function buildDocument(array $source, string $aiText): string
    {
        return $aiText . "\n";
    }

    private function runGit(array $arguments, string $workingDirectory): string
    {
        $process = new Process(array_merge(['git'], $arguments), $workingDirectory);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'Git command failed.');
        }

        return $process->getOutput();
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || str_starts_with($path, '\\') || (bool) preg_match('/^[A-Za-z]:\\\\/', $path);
    }
}
