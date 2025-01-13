<?php

namespace Hamoi1\EloquentEncryptAble\Console\Commands;

use Hamoi1\EloquentEncryptAble\Services\EloquentEncryptAbleService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'eloquent-encryptable:re-encrypt', description: 'Re-encrypts the data of the specified models using the Hill cipher.')]
class ReEncryptDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eloquent-encryptable:re-encrypt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '
        Re-encrypts the data of the specified models using the Hill cipher.
        The models must have the getEncryptAble method that returns an array of the columns to encrypt.
    ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $models = config('hill-cipher.models', []);
        if (empty($models)) {
            $this->error('No models found in the configuration file');

            return;
        }
        // ask the user to confirm the re-encryption
        $message = 'Are you sure you want to re-encrypt the data of the specified models ' . implode(', ', $models) . '?';
        if (! $this->confirm($message, true)) {
            $this->info('Re-encryption cancelled');

            return;
        }
        $this->info('Re-encrypting data using the Hill cipher...');
        $this->reEncryptModelData($models);
        $this->info('Data re-encrypted successfully using the Hill cipher');
    }

    protected function reEncryptModelData(array $models): void
    {
        foreach ($models as $modelClass) {
            $startTime = now(); // Set the start time
            $this->info('Re-encrypting ' . $modelClass . '...');

            $model = new $modelClass;
            $encryptAble = property_exists($model, 'encryptAble') ? $model->encryptAble : [];

            // Count the total number of records for the progress bar
            $totalRecords = $modelClass::count();
            $this->output->writeln('Total records to process: ' . $totalRecords);

            // Initialize the progress bar
            $progressBar = $this->output->createProgressBar($totalRecords);
            $progressBar->start();

            $modelClass::withoutEvents(function () use ($modelClass, $encryptAble, $progressBar) {
                $modelClass::chunk(100, function ($categories) use ($encryptAble, $progressBar) {
                    foreach ($categories as $category) {
                        $newCategory = app(EloquentEncryptAbleService::class)->reEncryptModelData($category->only($encryptAble), $encryptAble);
                        $category->fill($newCategory);
                        $category->save();

                        // Advance the progress bar after processing each record
                        $progressBar->advance();
                    }
                });
            });

            // Finish the progress bar
            $progressBar->finish();
            $this->newLine(); // Add a newline after the progress bar

            $endTime = now(); // End time
            $this->info('Time taken to re-encrypt ' . $modelClass . ': ' . $startTime->diffForHumans($endTime, true));
        }
    }
}
