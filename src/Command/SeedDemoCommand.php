<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:demo:seed',
    description: 'Réinitialise la base et provisionne les comptes Firebase de la démo (patient, médecin, admin).',
)]
final class SeedDemoCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'keep-db',
            null,
            InputOption::VALUE_NONE,
            'Ne purge pas la base (par défaut on relance les fixtures).',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $app = $this->getApplication();
        if (null === $app) {
            $io->error('Application console introuvable.');
            return Command::FAILURE;
        }

        if (!$input->getOption('keep-db')) {
            $io->section('1/4 — Reset DB + fixtures');
            if (Command::SUCCESS !== $app->find('doctrine:fixtures:load')->run(
                new ArrayInput(['--no-interaction' => true]),
                $output,
            )) {
                $io->error('Échec doctrine:fixtures:load.');
                return Command::FAILURE;
            }
        } else {
            $io->section('1/4 — DB conservée (--keep-db)');
        }

        $steps = [
            '2/4 — Compte admin' => ['app:demo:create-admin', []],
            '3/4 — Compte patient demo' => ['app:demo:create-patient', []],
            '4/4 — Médecin demo (Aymen Ben Ali)' => ['app:demo:link-doctor-firebase', [
                'email' => 'medecin@docconnect.tn',
                'password' => 'medecin1234',
                'doctorSlug' => 'dr-aymen-ben-ali',
            ]],
        ];

        foreach ($steps as $label => [$name, $args]) {
            $io->section($label);
            $code = $app->find($name)->run(new ArrayInput($args), $output);
            if (Command::SUCCESS !== $code) {
                $io->error(sprintf('Étape "%s" en échec.', $name));
                return Command::FAILURE;
            }
        }

        $io->success('Démo prête. Identifiants :');
        $io->listing([
            'Patient  → demo@docconnect.tn / demo1234',
            'Médecin  → medecin@docconnect.tn / medecin1234',
            'Admin    → admin@docconnect.tn / admin@docconnect',
        ]);

        return Command::SUCCESS;
    }
}
