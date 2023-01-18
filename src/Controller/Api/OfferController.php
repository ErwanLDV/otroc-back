<?php

namespace App\Controller\Api;

use App\Entity\Offer;
use App\Entity\User;
use App\Repository\OfferRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @OA\Tag(name="O'troc API : Offer")
 * @Security(name="bearerAuth")
 */
class OfferController extends AbstractController
{
    /**
     * Retrieves a list of the active offers
     * @Route("/api/offers", name="app_api_offers_browse", methods={"GET"})
     *
     *
     * 
     * @OA\Response(
     *     response="200",
     *     description="Retrieves the active offers",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Offer::class, groups={"offer_browse"}))
     *     )
     * )
     * @param OfferRepository $offerRepository
     * @return JsonResponse
     */
    public function browse(OfferRepository $offerRepository): JsonResponse
    {
        return $this->json(
            $offerRepository->findAll(),
            HttpFoundationResponse::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "offer_browse"
                ]
            ]
        );
    }

    /**
     * Retrieves the informations about a single offer thanks to its ID
     * @Route("/api/offers/{id<\d+>}", name="app_api_offers_read", methods={"GET"})
     *
     * @OA\Response(
     *     response="200",
     *     description="Returns JSON info of the offer",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Offer::class, groups={"offer_read"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=404,
     *     description="l'offre n\'a pas été trouvée"
     * )
     * @param Offer|null $offer
     * @return JsonResponse
     */
    public function read(Offer $offer = null): JsonResponse
    {
        if (!$offer) {
            return $this->json(["erreur" => "l'offre n\'a pas été trouvée"], HttpFoundationResponse::HTTP_NOT_FOUND);
        }
        return $this->json(
            $offer,
            HttpFoundationResponse::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "offer_read"
                ]
            ]
        );
    }

    /**
     * Adds a new offer 
     * @Route("/api/offers", name="app_api_offers_add", methods={"POST"})
     * 
     * @OA\Response(
     *     response="201",
     *     description="creates a new offer",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Offer::class, groups={"offer_read"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=400,
     *     description="Les données JSON envoyées n'ont pas pu être interprêtées"
     * )
     * @OA\Response(
     *     response=422,
     *     description="Renvoie un tableau d'erreurs en fonction des validations demandées pour les champs"
     * )
     * @OA\RequestBody(
     *     @Model(type=Offer::class, groups={"nelmio_add_offer"}),
     * )     
     * 
     * @param Request $request
     * @param SerializerInterface $serializerInterface
     * @param ValidatorInterface $validatorInterface
     * @param EntityManagerInterface $entityManagerInterface
     * @return JsonResponse
     */

    public function add(
        Request $request,
        SerializerInterface $serializerInterface,
        ValidatorInterface $validatorInterface,
        EntityManagerInterface $doctrine
    ): JsonResponse {
        $jsonContent = $request->getContent();

        try {
            $newOffer = $serializerInterface->deserialize($jsonContent, Offer::class, 'json');
        } catch (\Exception $e) {
            return $this->json(
                ["erreur" => "Les données JSON envoyées n'ont pas pu être interprêtées"],
                HttpFoundationResponse::HTTP_BAD_REQUEST
            );
        }

        $errors = $validatorInterface->validate($newOffer);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return $this->json(
                $errorsString,
                HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $newOffer->setUser($this->getUser());

        $doctrine->persist($newOffer);
        $doctrine->flush();

        return $this->json(
            $newOffer,
            HttpFoundationResponse::HTTP_CREATED,
            [],
            ["groups" => ["offer_read"]]
        );
    }

    /**
     * Edits an offer 
     * @Route("/api/offers/{id<\d+>}", name="app_api_offers_edit", methods={"PUT", "PATCH"})
     *
     * @OA\Response(
     *     response="201",
     *     description="creates a new offer",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Offer::class, groups={"offer_read"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=400,
     *     description="Les données JSON envoyées n'ont pas pu être interprêtées"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Il n\'existe pas d'offre' pour cet ID"
     * )
     * @OA\Response(
     *     response=422,
     *     description="Renvoie un tableau d'erreurs en fonction des validations demandées pour les champs"
     * )
     * @OA\RequestBody(
     *     @Model(type=Offer::class, groups={"nelmio_add_offer"}),
     * )     
     * @param Offer|null $offer
     * @param Request $request
     * @param EntityManagerInterface $doctrine
     * @param ValidatorInterface $validatorInterface
     * @param SerializerInterface $serializerInterface
     * @return JsonResponse
     */
    public function edit(
        ?Offer $offer,
        Request $request,
        EntityManagerInterface $doctrine,
        ValidatorInterface $validatorInterface,
        SerializerInterface $serializerInterface
    ): JsonResponse {
        if (!$offer) {
            return $this->json(["erreur" => "Il n\'existe pas d'offre' pour cet ID"], HttpFoundationResponse::HTTP_NOT_FOUND);
        }

        $jsonContent = $request->getContent();

        try {
            $editedOffer = $serializerInterface->deserialize($jsonContent, Offer::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $offer]);
        } catch (\Exception $e) {
            return $this->json(
                ["erreur" => "Les données JSON envoyées n'ont pas pu être interprêtées"],
                HttpFoundationResponse::HTTP_BAD_REQUEST
            );
        }

        $errors = $validatorInterface->validate($editedOffer);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return $this->json(
                $errorsString,
                HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        
        $offer->setIsReported(null);
        $offer->setUpdatedAt(new DateTime());
        $doctrine->flush();

        return $this->json(
            $offer,
            HttpFoundationResponse::HTTP_PARTIAL_CONTENT,
            [
                "Location" => $this->generateUrl("app_api_offers_read", ["id" => $offer->getId()])
            ],
            [
                "groups" =>
                [
                    "offer_read"
                ]
            ]
        );
    }

    /** 
     * Deletes an offer
     * @Route("/api/offers/{id<\d+>}", name="app_api_offers_delete", methods={"DELETE"})
     * 
     * @OA\Response(
     *     response="200",
     *     description="Returns JSON confirming that the offer has been deleted",
     * )
     * 
     * @OA\Response(
     *     response=404,
     *     description="Il n\'existe pas d'offre' pour cet ID"
     * )
     * @param Offer|null $offer
     * @param EntityManagerInterface $doctrine
     * @return JsonResponse
     */
    public function delete(?Offer $offer, EntityManagerInterface $doctrine, ParameterBagInterface $parameterBag): JsonResponse
    {
        if (!$offer) {
            return $this->json(["erreur" => "Il n\'existe pas d'offre' pour cet ID"]);
        }

        $oldPicture = ($offer->getPicture() !== null) ? $offer->getPicture() : "";
        if(str_contains($oldPicture, 'http://yannlebouc-server.eddi.cloud/projet-11-o-troc-back/public/img/')) {
            $pictureFile = str_replace('http://yannlebouc-server.eddi.cloud/projet-11-o-troc-back/public/img/', "", $oldPicture);
            unlink('/var/www/html/projet-11-o-troc-back/public/img/' . $pictureFile);
        }

        $doctrine->remove($offer);
        $doctrine->flush();

        return $this->json(
            ["validation" => "l'annonce " . $offer->getTitle() . " a bien été supprimée."],
            HttpFoundationResponse::HTTP_OK
        );
    }

    /** Retrieves all the offers containing a keyword in their title
     * 
     * @Route("/api/offers/results", name="app_api_offers_research", methods={"POST"})
     * @OA\Response(
     *     response="200",
     *     description="Returns JSON info of the offers",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Offer::class, groups={"offer_browse"}))
     *     )
     * )
     */
    public function offersResearch(Request $request, OfferRepository $offerRepository): JsonResponse
    {   
        

        $requestContent = $request->getContent();
        $keywords = explode(" ", $requestContent);
        $offers = [];
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 2) {
                $results = $offerRepository->findSearchedOffers($keyword);
                $offers[] = $results;
            }
        }
        
        return $this->json(
            $offers,
            HttpFoundationResponse::HTTP_OK,
            [],
            [
                'groups' => [
                    'offer_browse'
                ]
            ]
        );
    }

    /**
     * Method used to upload a picture for an offer 
     * @Route("/api/offers/{id<\d+>}/pictures", name="app_api_offer_add_picture", methods={"POST"})
     * 
     * @OA\Response(
     *     response="200",
     *     description="Validates the upload of the picture",
     * )
     * @OA\Response(
     *     response=415,
     *     description="Il y a un eu problème lors de la sauvegarde de l\'image"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Il n\'existe pas d'offre' pour cet ID"
     * )
     * 
     * @param Offer|null $offer
     * @param Request $request
     * @param EntityManagerInterface $doctrine
     * @return JsonResponse
     */
    public function uploadOfferPicture(
        ?Offer $offer,
        Request $request,
        EntityManagerInterface $doctrine
    ): JsonResponse
    {   
        if (!$offer) {
            return $this->json(["erreur" => "L'offre recherchée n'existe pas"], HttpFoundationResponse::HTTP_NOT_FOUND);
        }

        try {
            $image = $request->files->get('file');
            $imageName = uniqid() . '_' . $image->getClientOriginalName();
            $image->move('http://o-troc.fr:8000/img', $imageName);
        
            $offer->setPicture('http://localhost:8000/img/'.$imageName);
            // $offer->setPicture('http://yann-lebouc.vpnuser.lan:8081/img/'.$imageName);

            $doctrine->flush();
        } catch (\Exception $e) {
            return $this->json(['erreur' => 'Il y a un eu problème lors de la sauvegarde de l\'image'], HttpFoundationResponse::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        return $this->json(['success' => 'Image correctement importée'], HttpFoundationResponse::HTTP_OK);
    }

    /** Allows a user to set an offer lended status to true or false
    * 
    * @Route("/api/offers/{id<\d+>}/lend", name="app_api_offers_lended", methods={"PUT", "PATCH"})
    *
    * @OA\Response(
    *     response=404,
    *     description="Il n\'existe pas d'offre' pour cet ID"
    * )
    * @OA\Response(
    *     response=206,
    *     description="Validates the update of the isLended property"
    * )
    * @param Offer|null $offer
    * @param EntityManagerInterface $doctrine
    * @return JsonResponse
    */
    public function isLended(?Offer $offer, EntityManagerInterface $doctrine): JsonResponse
    {   
        if (!$offer) {
            return $this->json(["erreur" => "Il n\'existe pas d'offre' pour cet ID"]);
        }

        $isLended = !($offer->isIsLended());

        $offer->setIsLended($isLended);
        $doctrine->flush();

        return $this->json(['success' => 'Modification prise en compte'], HttpFoundationResponse::HTTP_PARTIAL_CONTENT);
    }

    /** Allows a user to set an offer active status to true or false
    * 
    * @Route("/api/offers/{id<\d+>}/active", name="app_api_offers_active", methods={"PUT", "PATCH"})
    *
    * @OA\Response(
    *     response=404,
    *     description="Il n\'existe pas d'offre' pour cet ID"
    * )
    * @OA\Response(
    *     response=206,
    *     description="Validates the update of the isActive property"
    * )
    * @param Offer|null $offer
    * @param EntityManagerInterface $doctrine
    * @return JsonResponse
    */
    public function isActive(?Offer $offer, EntityManagerInterface $doctrine): JsonResponse
    {   
        if (!$offer) {
            return $this->json(["erreur" => "Il n\'existe pas d'offre' pour cet ID"]);
        }

        $isActive = !($offer->isIsActive());

        $offer->setIsActive($isActive);
        $doctrine->flush();

        return $this->json(['success' => 'Modification prise en compte'], HttpFoundationResponse::HTTP_PARTIAL_CONTENT);
    }

    /** Allows a user to set an offer reported status to true or false
    * 
    * @Route("/api/offers/{id<\d+>}/reported", name="app_api_offers_reported", methods={"PUT", "PATCH"})
    *
    * @OA\Response(
    *     response=404,
    *     description="Il n\'existe pas d'offre' pour cet ID"
    * )
    * @OA\Response(
    *     response=206,
    *     description="Validates the update of the isReported property"
    * )
    * @param Offer|null $offer
    * @param EntityManagerInterface $doctrine
    * @return JsonResponse
    */
    public function isReported(?Offer $offer, EntityManagerInterface $doctrine): JsonResponse
    {   
        if (!$offer) {
            return $this->json(["erreur" => "Il n\'existe pas d'offre' pour cet ID"]);
        }

        $offer->setIsReported(true);
        $doctrine->flush();

        return $this->json(['success' => 'Modification prise en compte'], HttpFoundationResponse::HTTP_PARTIAL_CONTENT);
    }
}
