<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Command;

use Doctrine\DBAL\Driver\Connection;
use Ibexa\Core\FieldType\Image\ImageStorage\Gateway as ImageStorageGateway;
use Ibexa\Core\IO\Exception\BinaryFileNotFoundException;
use Ibexa\Core\IO\FilePathNormalizerInterface;
use Ibexa\Core\IO\IOServiceInterface;
use Ibexa\Core\IO\Values\BinaryFile;
use Ibexa\Core\IO\Values\BinaryFileCreateStruct;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
final class NormalizeImagesPathsCommand extends Command
{
    private const IMAGE_LIMIT = 100;
    private const BEFORE_RUNNING_HINTS = <<<EOT
<error>Before you continue:</error>
- Make sure to back up your database.
- Run this command in production environment using <info>--env=prod</info>
- Manually clear SPI/HTTP cache after running this command.
EOT;

    protected static $defaultName = 'ibexa:images:normalize-paths';

    /** @var \Ibexa\Core\FieldType\Image\ImageStorage\Gateway */
    private $imageGateway;

    /** @var \Ibexa\Core\IO\FilePathNormalizerInterface */
    private $filePathNormalizer;

    /** @var \Doctrine\DBAL\Driver\Connection */
    private $connection;

    /** @var \Ibexa\Core\IO\IOServiceInterface */
    private $ioService;

    public function __construct(
        ImageStorageGateway $imageGateway,
        FilePathNormalizerInterface $filePathNormalizer,
        Connection $connection,
        IOServiceInterface $ioService
    ) {
        parent::__construct();

        $this->imageGateway = $imageGateway;
        $this->filePathNormalizer = $filePathNormalizer;
        $this->connection = $connection;
        $this->ioService = $ioService;
    }

    protected function configure()
    {
        $beforeRunningHints = self::BEFORE_RUNNING_HINTS;

        $this
            ->setDescription('Normalizes stored paths for images.')
            ->setHelp(
                <<<EOT
The command <info>%command.name%</info> normalizes paths for images.

{$beforeRunningHints}
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Normalize image paths');

        $io->writeln([
            'Determining the number of images that require path normalization.',
            'It may take some time.',
        ]);

        $imagesCount = $this->imageGateway->countDistinctImages();
        $imagePathsToNormalize = [];
        $iterations = ceil($imagesCount / self::IMAGE_LIMIT);
        $io->progressStart($imagesCount);
        for ($i = 0; $i < $iterations; ++$i) {
            $imagesData = $this->imageGateway->getImagesData($i * self::IMAGE_LIMIT, self::IMAGE_LIMIT);

            foreach ($imagesData as $imageData) {
                $filePath = $imageData['filepath'];
                $normalizedImagePath = $this->filePathNormalizer->normalizePath($imageData['filepath']);
                if ($normalizedImagePath !== $filePath) {
                    $imagePathsToNormalize[] = [
                        'fieldId' => (int) $imageData['contentobject_attribute_id'],
                        'oldPath' => $filePath,
                        'newPath' => $normalizedImagePath,
                    ];
                }

                $io->progressAdvance();
            }
        }
        $io->progressFinish();

        $imagePathsToNormalizeCount = \count($imagePathsToNormalize);
        $io->note(sprintf('Found: %d', $imagePathsToNormalizeCount));
        if ($imagePathsToNormalizeCount === 0) {
            $io->success('No paths to normalize.');

            return 0;
        }

        if (!$io->confirm('Do you want to continue?')) {
            return 0;
        }

        $io->writeln('Normalizing image paths. Please wait...');
        $io->progressStart($imagePathsToNormalizeCount);

        $this->connection->beginTransaction();
        try {
            foreach ($imagePathsToNormalize as $imagePathToNormalize) {
                $this->updateImagePath(
                    $imagePathToNormalize['fieldId'],
                    $imagePathToNormalize['oldPath'],
                    $imagePathToNormalize['newPath'],
                    $io
                );
                $io->progressAdvance();
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
        }

        $io->progressFinish();
        $io->success('Done!');

        return 0;
    }

    private function updateImagePath(int $fieldId, string $oldPath, string $newPath, SymfonyStyle $io): void
    {
        $oldPathInfo = pathinfo($oldPath);
        $newPathInfo = pathinfo($newPath);
        // In Image's XML, basename does not contain a file extension, and the filename does - pathinfo results are exactly the opposite.
        $oldFileName = $oldPathInfo['basename'];
        $newFilename = $newPathInfo['basename'];
        $newBaseName = $newPathInfo['filename'];

        // Checking if a file exists physically
        $oldBinaryFile = $this->ioService->loadBinaryFileByUri(\DIRECTORY_SEPARATOR . $oldPath);
        try {
            $inputStream = $this->ioService->getFileInputStream($oldBinaryFile);
        } catch (BinaryFileNotFoundException $e) {
            $io->warning(sprintf('Skipping file %s as it doesn\'t exists physically.', $oldPath));

            return;
        }

        $xmlsData = $this->imageGateway->getAllVersionsImageXmlForFieldId($fieldId);
        foreach ($xmlsData as $xmlData) {
            $dom = new \DOMDocument();
            $dom->loadXml($xmlData['data_text']);

            /** @var \DOMElement $imageTag */
            $imageTag = $dom->getElementsByTagName('ezimage')->item(0);
            $this->imageGateway->updateImagePath($fieldId, $oldPath, $newPath);
            if ($imageTag && $imageTag->getAttribute('filename') === $oldFileName) {
                $imageTag->setAttribute('filename', $newFilename);
                $imageTag->setAttribute('basename', $newBaseName);
                $imageTag->setAttribute('dirpath', $newPath);
                $imageTag->setAttribute('url', $newPath);

                $this->imageGateway->updateImageData($fieldId, (int) $xmlData['version'], $dom->saveXML());
                $this->imageGateway->updateImagePath($fieldId, $oldPath, $newPath);
            }
        }

        $this->moveFile($oldFileName, $newFilename, $oldBinaryFile, $inputStream);
    }

    /**
     * @param resource $inputStream
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function moveFile(
        string $oldFileName,
        string $newFileName,
        BinaryFile $oldBinaryFile,
        $inputStream
    ): void {
        $newId = str_replace($oldFileName, $newFileName, $oldBinaryFile->id);

        $binaryCreateStruct = new BinaryFileCreateStruct(
            [
                'id' => $newId,
                'size' => $oldBinaryFile->size,
                'inputStream' => $inputStream,
                'mimeType' => $this->ioService->getMimeType($oldBinaryFile->id),
            ]
        );

        $newBinaryFile = $this->ioService->createBinaryFile($binaryCreateStruct);
        if ($newBinaryFile instanceof BinaryFile) {
            $this->ioService->deleteBinaryFile($oldBinaryFile);
        }
    }
}

class_alias(NormalizeImagesPathsCommand::class, 'eZ\Bundle\EzPublishCoreBundle\Command\NormalizeImagesPathsCommand');
