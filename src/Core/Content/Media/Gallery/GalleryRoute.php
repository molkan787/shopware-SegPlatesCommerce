<?php declare(strict_types=1);

namespace SegPlatesCommerce\Core\Content\Media\Gallery;

use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"store-api"})
 */
class GalleryRoute extends AbstractController
{

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaRepository;

    public function __construct(
        EntityRepositoryInterface $mediaRepository
    )
    {
        $this->mediaRepository = $mediaRepository;
    }

    /**
     * @Since("6.0.0.0")
     * @RouteScope(scopes={"store-api"})
     * @Route("/store-api/v3/gallery-media", name="store-api.gallery-media", methods={"GET"})
     */
    public function getGalleryMedia(Request $request): Response
    {
        $media = $this->searchMedia('4a4ffa1e03c2401d9cdb8eb065596519', Context::createDefaultContext());
        $items = [];
        foreach($media as $m){
            $metaData = $m->getMetaData();
            $translated = $m->getTranslated();
            array_push($items, array(
                'url' => $m->getUrl(),
                'alt' => $translated['alt'],
                'metaData' => $metaData,
                'thumbnails' => $m->getThumbnails()->getElements()
            ));
        }
        return new JsonResponse($items, Response::HTTP_OK);
    }

    private function searchMedia(string $folderId, Context $context): MediaCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mediaFolderId', $folderId));
        // $criteria->addAssociation('thumbnails');

        /** @var MediaCollection $media */
        $media = $this->mediaRepository
            ->search($criteria, $context)
            ->getEntities();

        return $media;
    }

}
