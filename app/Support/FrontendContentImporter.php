<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;
use Symfony\Component\Process\Process;

class FrontendContentImporter
{
    public function __construct(
        protected ?string $frontendPath = null,
    ) {
        $this->frontendPath ??= env('ILAMP_FRONTEND_PATH', base_path('../iLamp-frontend'));
    }

    public function canImport(): bool
    {
        return is_dir($this->frontendPath)
            && is_file($this->frontendPath.'/package.json')
            && is_dir($this->frontendPath.'/node_modules/typescript');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function load(): ?array
    {
        if (! $this->canImport()) {
            return null;
        }

        $process = new Process([
            'node',
            '-e',
            $this->nodeScript(),
            $this->frontendPath,
        ], base_path());

        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                'Unable to import frontend constants: '.trim($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        try {
            $decoded = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Frontend content importer returned invalid JSON.', previous: $exception);
        }

        $this->syncAssets();

        return $decoded;
    }

    public function syncAssets(): void
    {
        $this->copyDirectory(
            $this->frontendPath.'/src/assets/blog',
            public_path('uploads/blog'),
        );

        $this->copyDirectory(
            $this->frontendPath.'/src/assets/portfolio',
            public_path('uploads/portfolio'),
        );
    }

    protected function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($source)) {
            return;
        }

        File::ensureDirectoryExists($destination);

        foreach (File::files($source) as $file) {
            File::copy($file->getPathname(), $destination.DIRECTORY_SEPARATOR.$file->getFilename());
        }
    }

    protected function nodeScript(): string
    {
        return <<<'NODE'
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const frontendRoot = process.argv[1];
const ts = require(path.join(frontendRoot, 'node_modules', 'typescript'));

function loadTsModule(relativePath) {
  const file = path.join(frontendRoot, relativePath);
  const source = fs.readFileSync(file, 'utf8');
  const transpiled = ts.transpileModule(source, {
    compilerOptions: {
      module: ts.ModuleKind.CommonJS,
      target: ts.ScriptTarget.ES2020,
      esModuleInterop: true,
      jsx: ts.JsxEmit.ReactJSX,
    },
    fileName: file,
  }).outputText;

  const module = { exports: {} };
  const sandbox = {
    module,
    exports: module.exports,
    require: (spec) => {
      if (spec === 'lucide-react') {
        return new Proxy({}, { get: (_, key) => String(key) });
      }

      if (spec === 'next/image') {
        return {};
      }

      if (spec.startsWith('@/assets/')) {
        return spec.replace('@/assets/', '/uploads/');
      }

      if (spec.startsWith('@/')) {
        return {};
      }

      return require(spec);
    },
    __filename: file,
    __dirname: path.dirname(file),
    console,
  };

  vm.runInNewContext(transpiled, sandbox, { filename: file });

  return module.exports;
}

const content = {
  about: loadTsModule('src/constants/about.ts'),
  services: loadTsModule('src/constants/services.ts'),
  serviceDetails: loadTsModule('src/constants/serviceDetails.ts'),
  portfolio: loadTsModule('src/constants/portfolio.ts'),
  blog: loadTsModule('src/constants/blog.ts'),
  testimonials: loadTsModule('src/constants/testimonials.ts'),
};

process.stdout.write(JSON.stringify(content));
NODE;
    }
}
