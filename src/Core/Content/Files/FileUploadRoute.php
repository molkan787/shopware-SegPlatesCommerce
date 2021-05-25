<?php declare(strict_types=1);

namespace SegPlatesCommerce\Core\Content\Files;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\File\FileNameProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Content\Media\MediaCollection;

/**
 * @RouteScope(scopes={"store-api"})
 */
class FileUploadRoute extends AbstractController 
{
   private $mediaUpdater;
   private $fileNameProvider;
   private $systemConfigService;

   public function __construct(
       FileSaver $mediaUpdater,
       FileNameProvider $fileNameProvider      
    ) {
       $this->mediaUpdater = $mediaUpdater;
       $this->fileNameProvider = $fileNameProvider;     
    }

    /**
     * @RouteScope(scopes={"store-api"})
     * @Route("/store-api/v3/files/download", name="store-api.files.download", methods={"GET"}, defaults={"auth_required"=false})
     */
    public function getFile(Request $request){
        $mediaId = $request->get('mediaId');
        if(empty($mediaId)){
            throw new Exception('Missing parameter (mediaId)');
        }
        $media = $this->searchMedia($mediaId, Context::createDefaultContext());
        $file = $media->get($mediaId);
        if($file){
            return new RedirectResponse($file->getUrl());
        }
        throw new Exception('Media not found');
    }
    
     /**
     * @RouteScope(scopes={"store-api"})
     * @Route("/store-api/v3/files/upload", name="store-api.files.upload", methods={"POST"})
     */
    public function uploadFiles(Request $request)
    {
        $data = $request->files;
        $testSupportedExtension = array('gif', 'png', 'jpg', 'jpeg', 'pdf', 'xlsx', 'txt', 'docx');
        
        $context = Context::createDefaultContext();
        $mediaRepository = $this->container->get('media.repository');
        $results = array();
        $error = false;
        $message = '';
        foreach ($data as $file) {
            $fileName = $file->getClientOriginalName();
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            if (!in_array($ext,$testSupportedExtension) ) {
                $error = true;
                $message = 'Invalid Extension';
                break;
            } else {
                $fileName = $fileName . Random::getInteger(100000, 999999);
                
                $mediaId = Uuid::randomHex();
                $media = [
                    [
                        'id' => $mediaId,
                        'name' => $fileName,
                        'fileName' => $fileName,
                        'mimeType' => $file->getClientMimeType(),
                        'fileExtension' => $file->guessExtension(),
                     ]
                 ];
                    
                $mediaId = $mediaRepository->create($media, Context::createDefaultContext())->getEvents()->getElements()[1]->getIds()[0];
                if (is_array($mediaId)) {
                    $mediaId = $mediaId['mediaId'];
                }
                $result;
                try {
                    $result = $this->upload($file, $fileName, $mediaId, $context);                
                } catch (\Exception $exception) {
                    $fileName = $fileName . Random::getInteger(100000, 999999);
                    $result =  $this->upload($file, $fileName, $mediaId, $context);
                }
                array_push($results, $mediaId);
            }
        }
        if($error){
            throw new Exception($message);
        }else{
            return new JsonResponse($results, Response::HTTP_OK);
        }
    } 

    private function upload($file, $fileName, $mediaId, $context)
    {   
        return $this->mediaUpdater->persistFileToMedia(
            new MediaFile(
                $file->getRealPath(),
                $file->getMimeType(),
                $file->guessExtension(),
                $file->getSize()
            ),
            $this->fileNameProvider->provide(
                $fileName,
                $file->getExtension(),
                $mediaId,
                $context
            ),
            $mediaId,
            $context
        );  
    }

    private function searchMedia(string $mediaId, Context $context): MediaCollection
    {
        $mediaRepository = $this->container->get('media.repository');

        $criteria = new Criteria([$mediaId]);

        /** @var MediaCollection $media */
        $media = $mediaRepository
            ->search($criteria, $context)
            ->getEntities();

        return $media;
    }
}