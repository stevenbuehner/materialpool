<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

final class EvaluationDatasetProgress {
	private ?ProgressBar $bar = NULL;

	private ?string $phase = NULL;

	public function __construct(private readonly Command $command) {
	}

	public function __invoke(string $phase, int $current, int $total): void {
		if ($this->phase !== $phase) {
			if ($this->bar !== NULL) {
				$this->command->newLine();
			}

			$this->phase = $phase;
			$this->bar   = $this->command->getOutput()->createProgressBar(max(1, $total));
			$this->bar->setFormat('%percent:3s%% [%bar%] %message%');
			$this->bar->setMessage($phase);
			$this->bar->start();
		} elseif ($this->bar->getMaxSteps() !== max(1, $total)) {
			$this->bar->setMaxSteps(max(1, $total));
		}

		$this->bar->setProgress($current);
	}

	public function finish(): void {
		if ($this->bar !== NULL) {
			$this->bar->finish();
			$this->command->newLine();
		}
	}

	public function abort(): void {
		if ($this->bar !== NULL) {
			$this->command->newLine();
		}
	}
}
