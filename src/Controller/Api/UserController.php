<?php

namespace App\Controller\Api;

use App\Entity\Offer;
use App\Entity\User;
use App\Entity\Wish;
use App\Repository\OfferRepository;
use App\Repository\UserRepository;
use App\Repository\WishRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Json;
use ProxyManager\Factory\RemoteObject\Adapter\JsonRpc;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Security as CoreSecurity;

/**
 * @OA\Tag(name="O'troc API : User")
 * @Security(name="bearerAuth")
 */
class UserController extends AbstractController
{

    /**
     * Retrieves a list of the offers belonging to the connected user thanks to the related jwttoken
     *
     * @Route("/api/users/current/offers", name="app_api_current_user_offers", methods={"GET"})
     * @OA\Response(
     *     response="200",
     *     description="Retrieves the offers list of the connected user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"current_user_offers"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=404,
     *     description="Nous avons eu un problème lors de la récupération de votre profil, merci de vous reconnecter"
     * )
     * 
     * @param OfferRepository $offerRepository
     * @return JsonResponse
     */
    public function getMyOffers(OfferRepository $offerRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['erreur' => 'Erreur lors de la récupération du profil, merci de vous reconnecter'], HttpFoundationResponse::HTTP_NOT_FOUND);
        }

        $offers = $offerRepository->findUsersActiveOffers($user->getId());

        return $this->json(
            $offers,
            HttpFoundationResponse::HTTP_OK,
            [],
            [
                'groups' =>
                [
                    'current_user_offers'
                ]
            ]
        );
    }

    /**
     * Retrieves a list of the offers belonging to the connected user thanks to the related jwttoken
     *
     * @Route("/api/users/current/wishes", name="app_api_current_user_wishes", methods={"GET"})
     * 
     * @OA\Response(
     *     response="200",
     *     description="Retrieves the wishes list of the connected user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"current_user_wishes"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=404,
     *     description="Nous avons eu un problème lors de la récupération de votre profil, merci de vous reconnecter"
     * )     
     * 
     * @param WishRepository $wishRepository
     * @return JsonResponse
     */
    public function getMyWishes(WishRepository $wishRepository): JsonResponse
    {

        $user = $this->getUser();

        if (!$user) {
            return $this->json(['erreur' => 'Erreur lors de la récupération du profil, merci de vous reconnecter'], HttpFoundationResponse::HTTP_NOT_FOUND);
        }

        $wishes = $wishRepository->findUserActiveWishes($user->getId());

        return $this->json(
            $wishes,
            HttpFoundationResponse::HTTP_OK,
            [],
            [
                'groups' =>
                [
                    'current_user_wishes'
                ]
            ]
        );
    }
    /**
     * Retrieves a list of the inactive advertisement belonging to the connected user 
     *
     * @Route("/api/users/current/advertisements", name="app_api_current_user_ads", methods={"GET"})
     * 
     * @OA\Response(
     *     response="200",
     *     description="Retrieves the inactive ads list of the connected user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"offer_read", "wish_read"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=404,
     *     description="Nous avons eu un problème lors de la récupération de votre profil, merci de vous reconnecter"
     * )     
     * 
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function getUsersInactiveAds(OfferRepository $offerRepository, WishRepository $wishRepository): JsonResponse
    {

        $user = $this->getUser();

        if (!$user) {
            return $this->json(['erreur' => 'Erreur lors de la récupération du profil, merci de vous reconnecter'], HttpFoundationResponse::HTTP_NOT_FOUND);
        }

        $wishes = $wishRepository->findUserInactiveWishes($user->getId());
        $offers = $offerRepository->findUserInactiveOffers($user->getId());

        return $this->json(
            [
                'wishes' => $wishes,
                'offers' => $offers
            ],
            HttpFoundationResponse::HTTP_OK,
            [],
            [
                'groups' =>
                [
                    'wish_read',
                    'offer_read'
                ]
            ]
        );
    }
    /**
     * Retrieves a list of the offers belonging to a user thanks to its ID
     * @Route("/api/users/{id<\d+>}/offers", name="app_api_users_offers", methods={"GET"})
     * 
     * @OA\Response(
     *     response="200",
     *     description="Retrieves the offer list of a user thanks to its ID",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Offer::class, groups={"user_offer_browse"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=404,
     *     description="Nous avons eu un problème lors de la récupération de votre profil, merci de vous reconnecter"
     * )          
     * @param User|null $user
     * @return JsonResponse
     */
    public function userOfferBrowse(?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(["erreur" => "la demande n\'a pas été trouvée"], HttpFoundationResponse::HTTP_NOT_FOUND);
        }

        return $this->json(
            $user,
            HttpFoundationResponse::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "user_offer_browse"
                ]
            ]
        );
    }


    /**
     * Method handling the users signup form
     * @Route("/api/users", name="app_api_users_add", methods={"POST"})
     * 
     * @OA\Response(
     *     response="201",
     *     description="Allows a visitor to create an account and become a user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"users_read"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=400,
     *     description="Les données JSON envoyées n'ont pas pu être intérpêtées"
     * )
     * @OA\Response(
     *     response=422,
     *     description="Renvoie un tableau d'erreurs en fonction des validations demandées pour les champs"
     * )
     * @OA\RequestBody(
     *     @Model(type=User::class, groups={"nelmio_add_user"}),
     * )
     * @param Request $request
     * @param SerializerInterface $serializerInterface
     * @param ValidatorInterface $validatorInterface
     * @param EntityManagerInterface $entityManagerInterface
     * @return Response
     */

    public function add(
        Request $request,
        SerializerInterface $serializerInterface,
        ValidatorInterface $validatorInterface,
        EntityManagerInterface $doctrine,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $jsonContent = $request->getContent();

        try {
            $newUser = $serializerInterface->deserialize($jsonContent, User::class, 'json');
        } catch (\Exception $e) {
            return $this->json(
                ["erreur" => "Les données JSON envoyées n'ont pas pu être interprêtées"],
                HttpFoundationResponse::HTTP_BAD_REQUEST
            );
        }

        $errors = $validatorInterface->validate($newUser);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return $this->json(
                $errorsString,
                HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $newUser->setPicture('http://back.o-troc.fr/assets/images/sbcf-default-avatar.png');
        $hashedPassword = $passwordHasher->hashPassword($newUser, $newUser->getPassword());
        $newUser->setPassword($hashedPassword);

        $doctrine->persist($newUser);
        $doctrine->flush();

        return $this->json(
            $newUser,
            HttpFoundationResponse::HTTP_CREATED,
            [
                // "Location" => $this->generateUrl("app_api_wishes_read", ["id" => $newUser->getId()])
            ],
            ["groups" => ["users_read"]]
        );
    }

    /**
     * Retrieves the informations from a particular user from his ID
     * @Route("/api/users/{id<\d+>}", name="app_api_users_read", methods={"GET"})
     * @OA\Response(
     *     response="200",
     *     description="Retrieves the informations from a particular user from his ID",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"user_ads_browse"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=404,
     *     description="L'utilisateur recherché n'existe pas"
     * )          
     * @param User|null $user
     * @return JsonResponse
     */
    public function read(?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(["erreur" => "L'utilisateur recherché n'existe pas"], HttpFoundationResponse::HTTP_NOT_FOUND);
        }

        return $this->json(
            $user,
            HttpFoundationResponse::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "user_ads_browse"
                ]
            ]
        );
    }

    /**
     * Retrieves the informations of the connected user
     * 
     * @Route("/api/users/current/profile", name="app_api_users_profile", methods={"GET"})
     * 
     * @OA\Response(
     *     response="200",
     *     description="Retrieves the informations of the connected user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"users_read"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=404,
     *     description="Nous avons eu un problème lors de la récupération de votre profil, merci de vous reconnecter"
     * )     
     * @return JsonResponse
     */
    public function getMyProfile(): JsonResponse
    {

        $user = $this->getUser();

        if (!$user) {
            return $this->json(['erreur' => 'Erreur lors de la récupération du profil, merci de vous reconnecter'], HttpFoundationResponse::HTTP_NOT_FOUND);
        }
        
        return $this->json(
            $user,
            HttpFoundationResponse::HTTP_OK,
            [],
            [
                'groups' =>
                [
                    'users_read'
                ]
            ]
        );
    }

    /**
     * Retrieves the list of all the users in the database
     * @Route("/api/users", name="app_api_users_browse", methods={"GET"})
     * 
     * @OA\Response(
     *     response="200",
     *     description="Retrieves the list of all the users in the database",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"users_browse"}))
     *     )
     * )
     * 
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function browse(UserRepository $userRepository): JsonResponse
    {
        return $this->json(
            $userRepository->findAll(),
            HttpFoundationResponse::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "users_browse"
                ]
            ]
        );
    }

    /**
     * Method handling the connected user's editing form 
     * @Route("/api/users/current", name="app_api_user_edit", methods={"PUT", "PATCH"})
     * 
     * @OA\Response(
     *     response="206",
     *     description="Method handling the connected user's editing form",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"users_read"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=400,
     *     description="Les données JSON envoyées n'ont pas pu être intérpêtées"
     * )
     * @OA\Response(
     *     response=422,
     *     description="Renvoie un tableau d'erreurs en fonction des validations demandées pour les champs"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Erreur lors de la récupération du profil, merci de vous reconnecter"
     * )
     * @OA\RequestBody(
     *     @Model(type=User::class, groups={"nelmio_edit_user"}),
     * )
     */
    public function edit(
        EntityManagerInterface $doctrine,
        SerializerInterface $serializerInterface,
        Request $request,
        ValidatorInterface $validatorInterface
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['erreur' => 'Erreur lors de la récupération du profil, merci de vous reconnecter'], HttpFoundationResponse::HTTP_NOT_FOUND);
        }

        $jsonContent = $request->getContent();

        try {
            $editedUser = $serializerInterface->deserialize($jsonContent, User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
        } catch (\Exception $e) {
            return $this->json(['erreur' => 'Les données envoyées n\'ont pas pu être intérprêtées'], HttpFoundationResponse::HTTP_BAD_REQUEST);
        }

        $errors = $validatorInterface->validate($editedUser);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return $this->json(
                $errorsString,
                HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $editedUser->setUpdatedAt(new DateTime());

        $doctrine->flush();

        return $this->json(
            $user,
            HttpFoundationResponse::HTTP_PARTIAL_CONTENT,
            [],
            [
                "groups" => [
                    "users_read"
                ]
            ]
        );
    }

    /** Allows a user to edit its password
     * @Route("/api/users/current/password", name="app_api_users_edit_password", methods={"PUT", "PATCH"})
     * @OA\Response(
     *     response="206",
     *     description="Validates the modification of the password",
     * )
     * 
     * @OA\Response(
     *     response=400,
     *     description="Les données JSON envoyées n'ont pas pu être intérpêtées"
     * )
     * @OA\Response(
     *     response=417,
     *     description="La confirmation de mot de passe a échoué"
     * )
     * @OA\Response(
     *     response=406,
     *     description="Le mot de passe actuel est incorrect"
     * )
     */
    public function editPassword(
        Request $request, 
        EntityManagerInterface $doctrine, 
        UserPasswordHasherInterface $passwordHasher
        ): JsonResponse
    {
        $user = $this->getUser();

        try {
            $dataArray = json_decode($request->getContent(), true);
        } catch (\Exception $e) {
            return $this->json(
                ["erreur" => "Les données JSON envoyées n'ont pas pu être interprêtées"],
                HttpFoundationResponse::HTTP_BAD_REQUEST
            );
        }

        $currentPassword = (key_exists('currentpassword', $dataArray)) ? $dataArray['currentpassword'] : null;
        $newPassword = (key_exists('newpassword', $dataArray)) ? $dataArray['newpassword'] : null;
        $passwordConfirmation = (key_exists('passwordconfirmation', $dataArray)) ? $dataArray['passwordconfirmation'] : null;

        if($newPassword !== $passwordConfirmation) {
            return $this->json(['erreur' => 'Il y a eu une erreur lors de la confirmation du mot de passe, merci de réessayer'], HttpFoundationResponse::HTTP_EXPECTATION_FAILED);
        }

        if(!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->json(['erreur' => 'Mot de passe actuel incorrect'], HttpFoundationResponse::HTTP_NOT_ACCEPTABLE);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $doctrine->flush();

        return $this->json(
            ['Validation' => 'Votre mot de passe a bien été modifié.'],
            HttpFoundationResponse::HTTP_PARTIAL_CONTENT
        );
    }


    /**
     * Allows a user to edit/upload his profile picture
     * @Route("/api/users/current/pictures", name="app_api_user_add_picture", methods={"POST"})
     * 
     * @OA\Response(
     *     response=404,
     *     description="L'utilisateur recherché n'existe pas"
     * )
     * @OA\Response(
     *     response=415,
     *     description="Il y a un eu problème lors de la sauvegarde de l'image"
     * )
     * @OA\Response(
     *     response=200,
     *     description="Image correctement importée"
     * )
     * 
     * @param Request $request
     * @param EntityManagerInterface $doctrine
     * @return JsonResponse
     */
    public function uploadProfilePicture(
        Request $request, 
        EntityManagerInterface $doctrine): JsonResponse
    {   

        $user = $this->getUser();

        if (!$user) {
            return $this->json(["erreur" => "L'utilisateur recherché n'existe pas"], HttpFoundationResponse::HTTP_NOT_FOUND);
        }

        $oldPicture = $user->getPicture();
        if(str_contains($oldPicture, 'http://back.o-troc.fr/img/')) {
            $pictureFile = str_replace('http://back.o-troc.fr/img/', "", $oldPicture);
            unlink('http://back.o-troc.fr/img' . $pictureFile);
        }

        try {
            $image = $request->files->get('file');
            $imageName = uniqid() . '_' . $image->getClientOriginalName();
            $image->move('http://o-troc.fr:8000/img', $imageName);
        
            $user->setPicture('http://back.o-troc.fr/img/'.$imageName);

            $doctrine->flush();
        } catch (\Exception $e) {
            return $this->json(['erreur' => 'Il y a un eu problème lors de la sauvegarde de l\'image'], HttpFoundationResponse::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        return $this->json(['success' => 'Image correctement importée'], HttpFoundationResponse::HTTP_OK);
    }

    /** Allows a user to delete its profile
     * @Route("/api/users/current", name="app_api_users_delete", methods={"DELETE"})
     * 
     * @OA\Response(
     *     response=400,
     *     description="Il y a eu une erreur lors de la suppression"
     * )
     * @OA\Response(
     *     response=200,
     *     description="L'utilisateur a bien été supprimé"
     * )
     */
    public function delete(EntityManagerInterface $doctrine): JsonResponse
    {
        $user = $this->getUser();

        try {
            $doctrine->remove($user);
        } catch (\Exception $e) {
            return $this->json(['erreur' => 'Il y a eu une erreur lors de la suppression'], HttpFoundationResponse::HTTP_BAD_REQUEST);
        }
        
        $doctrine->flush();
        
        return $this->json(['success' => 'L\'utilisateur a bien été supprimé'], HttpFoundationResponse::HTTP_OK);
    }
}
