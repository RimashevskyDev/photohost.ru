<?php

namespace App\Controller;

use App\Entity\UploadedImage;
use App\Form\UploadImageType;
use App\Repository\UploadedImageRepository;
use App\Service\DocumentInteract;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageUploadController extends AbstractController
{

    /** @var int */
    public const PAGINATE = 5;

    public function __construct(
        private readonly EntityManagerInterface  $entityManager,
        private readonly DocumentInteract        $documentInteract,
        private readonly UploadedImageRepository $uploadedImageRepository
    )
    {
    }

    /**
     * @throws Exception
     */
    #[Route('/', name: 'app_image_index', priority: 2)]
    public function show(Request $request): Response
    {
        if (!ctype_digit($page = $request->query->get('page', 1))) {
            $page = 1;
        }

        $posts = $this->uploadedImageRepository->getAll($page);

        [
            $totalPostsReturned,
            $iterator,
            $maxPages
        ] = [
            $posts->getIterator()->count(),
            $posts->getIterator(),
            ceil($posts->count() / self::PAGINATE)
        ];

        return $this->render('image_index.html.twig', [
            'holder' => '/uploads',
            'paginator' => compact('maxPages', 'page', 'totalPostsReturned', 'iterator')
        ]);
    }

    /**
     * @throws NonUniqueResultException
     * @throws Exception
     */
    #[Route('/upload', name: 'app_image_upload', priority: 1)]
    public function upload(Request $request): Response
    {
        $formUpload = $this->createForm(UploadImageType::class);
        $formUpload->handleRequest($request);

        if ($formUpload->isSubmitted() && $formUpload->isValid()) {

            $formData = $formUpload->get('images')->getData();

            if ($formData != NULL) {
                $documentUpload = $this->documentInteract->uploadAllDocuments($formUpload->get('images')->getData(), $this->getParameter('image_upload_directory'));

                if ($documentUpload == NULL || $documentUpload == []) {
                    $this->addFlash('error', 'Ошибка при загрузке фотографий!');
                    return $this->redirectToRoute('app_image_upload');
                }
            } else {
                $this->addFlash('error', 'Ошибка при загрузке фотографий!');
                return $this->redirectToRoute('app_image_upload');
            }

            try {

                /** @var UploadedFile $image */
                foreach ($documentUpload as $modifiedName => $originalName) {
                    $entity = new UploadedImage();

                    $entity->setUploadedDate(new DateTime());
                    $entity->setOriginalName($originalName);
                    $entity->setModifiedName($modifiedName);

                    $this->entityManager->persist($entity);
                }

                $this->entityManager->flush();

            } catch (Exception $exception) {
                $this->addFlash('error', $exception->getMessage());
                return $this->redirectToRoute('app_image_upload');
            }

            $this->addFlash('success', 'Фотографии были загружены!');
            return $this->redirectToRoute('app_image_upload');
        }

        return $this->render('image_upload.html.twig', [
            'form' => $formUpload->createView()
        ]);
    }

    #[Route('/download/{id}', name: 'app_image_download', priority: 3)]
    public function downloadImage(?string $id = NULL): Response
    {
        /** @var UploadedImage $image */
        $image = $this->uploadedImageRepository->getById($id);

        if ($id == NULL || $image == NULL) {
            $this->addFlash('error', 'Файл не найден!');
            return $this->redirectToRoute('app_image_download');
        }

        $downloadDocument = $this->documentInteract->downloadDocument($this->getParameter('image_upload_directory'), $image->getModifiedName(), $image->getOriginalName());

        if (is_int($downloadDocument) && $downloadDocument == DocumentInteract::DOWNLOAD_ERROR_EXISTS) {
            $this->addFlash('error', 'Файл не найден!');
            return $this->redirectToRoute('app_image_download');
        }

        return $downloadDocument;
    }

    #[Route('/api/get/{id}', name: 'app_image_api_get', priority: 3)]
    public function apiGetById(?string $id = NULL): JsonResponse
    {
        /** @var UploadedImage $image */
        $image = $this->uploadedImageRepository->getById($id);

        if ($id == NULL || $image == NULL) {
            return new JsonResponse(NULL);
        }

        return new JsonResponse([
            'id' => $image->getId(),
            'originalName' => $image->getOriginalName(),
            'modifiedName' => $image->getModifiedName(),
            'uploadedDate' => $image->getUploadedDate()
        ]);
    }
}
