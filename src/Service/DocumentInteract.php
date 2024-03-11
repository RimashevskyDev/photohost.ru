<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DocumentInteract
{

    /** @var int */
    public const VALIDATE_SUCCESS = 0;
    /** @var int */
    public const VALIDATE_ERROR_FORMAT = 1;
    /** @var int */
    public const VALIDATE_ERROR_SIZE = 2;
    /** @var int */
    public const DOWNLOAD_ERROR_EXISTS = 0;
    /** @var Filesystem */
    private Filesystem $fileSystem;

    /**
     * @param Filesystem $fileSystem
     */
    public function __construct(Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    /**
     * @param array $documentList
     * @param string $documentPath
     * @return array|null
     */
    public function uploadAllDocuments(array $documentList, string $documentPath): null|array
    {
        $documentFiles = [];

        /** @var UploadedFile $documentFile */
        foreach ($documentList as $documentFile) {

            $fileName = md5(uniqid()) . '.' . pathinfo($documentFile->getClientOriginalName(), PATHINFO_EXTENSION);

            try {
                $documentFile->move(
                    $documentPath,
                    $fileName
                );

                $documentFiles[$fileName] = pathinfo($documentFile->getClientOriginalName(), PATHINFO_FILENAME);

            } catch (FileException) {
                if ($documentFiles != []) {
                    $this->removeAllDocuments($documentFiles, $documentPath);
                }

                return NULL;
            }
        }

        return $documentFiles;
    }

    /**
     * @param array $documentFiles
     * @param string $documentPath
     * @return void
     */
    public function removeAllDocuments(array $documentFiles, string $documentPath): void
    {
        $removeFiles = [];

        foreach ($documentFiles as $fileName => $originalName) {
            if (!$this->fileSystem->exists($filePath = $documentPath . '/' . $fileName)) {
                continue;
            }

            $removeFiles[] = $filePath;
        }

        if ($removeFiles != []) {
            $this->fileSystem->remove($removeFiles);
        }
    }

    /**
     * @param string $documentPath
     * @param string $documentUniqueName
     * @param string $documentOriginalName
     * @return BinaryFileResponse|int
     */
    public function downloadDocument(string $documentPath, string $documentUniqueName, string $documentOriginalName): BinaryFileResponse|int
    {
        if (!$this->fileSystem->exists($documentFullPath = $documentPath . '/' . $documentUniqueName)) {
            return self::DOWNLOAD_ERROR_EXISTS;
        }

        $response = new BinaryFileResponse($documentFullPath);

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $documentOriginalName . '.' . pathinfo($documentFullPath, PATHINFO_EXTENSION)
        );

        return $response;
    }
}