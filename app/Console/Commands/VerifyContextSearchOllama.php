<?php

namespace App\Console\Commands;

use App\Services\ContextSearch\Ollama\OllamaEmbeddingPool;
use Illuminate\Console\Command;
use Throwable;

final class VerifyContextSearchOllama extends Command {
	protected $signature = 'context-search:ollama:verify';

	protected $description = 'Prüft Modell-Digest, Dimension und Probevektor auf allen Kontextsuche-Ollama-Servern.';

	public function handle(OllamaEmbeddingPool $pool): int {
		try {
			$servers = $pool->verifyProfile();
		} catch (Throwable $exception) {
			report($exception);
			$this->components->error($exception->getMessage());

			return self::FAILURE;
		}

		$profile = $pool->profile();
		$this->components->info("Embedding-Profil {$profile->id()} ist auf allen Ollama-Servern verifiziert.");
		$this->table(['Server', 'Digest', 'Dimensionen'], $servers);

		return self::SUCCESS;
	}
}
