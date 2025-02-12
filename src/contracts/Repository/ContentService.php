<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentDraftList;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentList;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentMetadataUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationList;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class provides service methods for managing content.
 */
interface ContentService
{
    /**
     * Loads a content info object.
     *
     * To load fields use loadContent
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to read the content
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException - if the content with the given id does not exist
     *
     * @param int $contentId
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
     */
    public function loadContentInfo(int $contentId): ContentInfo;

    /**
     * Bulk-load ContentInfo items by id's.
     *
     * Note: It does not throw exceptions on load, just skips erroneous (NotFound or Unauthorized) ContentInfo items.
     *
     * @param int[] $contentIds
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo[] list of ContentInfo with Content Ids as keys
     */
    public function loadContentInfoList(array $contentIds): iterable;

    /**
     * Loads a content info object for the given remoteId.
     *
     * To load fields use loadContent
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to read the content
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException - if the content with the given remote id does not exist
     *
     * @param string $remoteId
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
     */
    public function loadContentInfoByRemoteId(string $remoteId): ContentInfo;

    /**
     * Loads a version info of the given content object.
     *
     * If no version number is given, the method returns the current version
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException - if the version with the given number does not exist
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to load this version
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     * @param int|null $versionNo the version number. If not given the current version is returned.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo
     */
    public function loadVersionInfo(ContentInfo $contentInfo, ?int $versionNo = null): VersionInfo;

    /**
     * Loads a version info of the given content object id.
     *
     * If no version number is given, the method returns the current version
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException - if the version with the given number does not exist
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to load this version
     *
     * @param int $contentId
     * @param int|null $versionNo the version number. If not given the current version is returned.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo
     */
    public function loadVersionInfoById(int $contentId, ?int $versionNo = null): VersionInfo;

    /**
     * Loads content in a version for the given content info object.
     *
     * If no version number is given, the method returns the current version
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException - if version with the given number does not exist
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to load this version
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     * @param array $languages A language priority, filters returned fields and is used as prioritized language code on
     *                         returned value object. If not given all languages are returned.
     * @param int|null $versionNo the version number. If not given the current version is returned from $contentInfo
     * @param bool $useAlwaysAvailable Add Main language to \$languages if true (default) and if alwaysAvailable is true
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    public function loadContentByContentInfo(ContentInfo $contentInfo, array $languages = null, ?int $versionNo = null, bool $useAlwaysAvailable = true): Content;

    /**
     * Loads content in the version given by version info.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to load this version
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo
     * @param string[] $languages A language priority, filters returned fields and is used as prioritized language code on
     *                         returned value object. If not given all languages are returned.
     * @param bool $useAlwaysAvailable Add Main language to \$languages if true (default) and if alwaysAvailable is true
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    public function loadContentByVersionInfo(VersionInfo $versionInfo, array $languages = null, bool $useAlwaysAvailable = true): Content;

    /**
     * Loads content in a version of the given content object.
     *
     * If no version number is given, the method returns the current version
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the content or version with the given id and languages does not exist
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     *
     * @param mixed $contentId
     * @param string[] $languages A language priority, filters returned fields and is used as prioritized language code on
     *                         returned value object. If not given all languages are returned.
     * @param int|null $versionNo the version number. If not given the current version is returned
     * @param bool $useAlwaysAvailable Add Main language to \$languages if true (default) and if alwaysAvailable is true
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    public function loadContent(int $contentId, array $languages = null, ?int $versionNo = null, bool $useAlwaysAvailable = true): Content;

    /**
     * Loads content in a version for the content object reference by the given remote id.
     *
     * If no version is given, the method returns the current version
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException - if the content or version with the given remote id does not exist
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     *
     * @param string $remoteId
     * @param string[] $languages A language priority, filters returned fields and is used as prioritized language code on
     *                         returned value object. If not given all languages are returned.
     * @param int|null $versionNo the version number. If not given the current version is returned
     * @param bool $useAlwaysAvailable Add Main language to \$languages if true (default) and if alwaysAvailable is true
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    public function loadContentByRemoteId(string $remoteId, array $languages = null, ?int $versionNo = null, bool $useAlwaysAvailable = true): Content;

    /**
     * Bulk-load Content items by the list of ContentInfo Value Objects.
     *
     * Note: it does not throw exceptions on load, just ignores erroneous Content item.
     * Moreover, since the method works on pre-loaded ContentInfo list, it is assumed that user is
     * allowed to access every Content on the list.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo[] $contentInfoList
     * @param string[] $languages A language priority, filters returned fields and is used as prioritized language code on
     *                            returned value object. If not given all languages are returned.
     * @param bool $useAlwaysAvailable Add Main language to \$languages if true (default) and if alwaysAvailable is true,
     *                                 unless all languages have been asked for.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content[] list of Content items with Content Ids as keys
     */
    public function loadContentListByContentInfo(array $contentInfoList, array $languages = [], bool $useAlwaysAvailable = true): iterable;

    /**
     * Creates a new content draft assigned to the authenticated user.
     *
     * If a different userId is given in $contentCreateStruct it is assigned to the given user
     * but this required special rights for the authenticated user
     * (this is useful for content staging where the transfer process does not
     * have to authenticate with the user which created the content object in the source server).
     * The user has to publish the draft if it should be visible.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to create the content in the given location
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if there is a provided remote ID which exists in the system or multiple Locations
     *                                                                        are under the same parent or if the a field value is not accepted by the field type
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException if a field in the $contentCreateStruct is not valid
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException if a required field is missing or is set to an empty value
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct $contentCreateStruct
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct[] $locationCreateStructs an array of {@link \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct} for each location parent under which a location should be created for the content
     *                                                                                                While optional, it's highly recommended to use Locations for content as a lot of features in the system is usually tied to the tree structure (including default Role policies).
     * @param string[]|null $fieldIdentifiersToValidate List of field identifiers for partial validation or null
     *                      for case of full validation. Empty identifiers array is equal to no validation.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content - the newly created content draft
     */
    public function createContent(ContentCreateStruct $contentCreateStruct, array $locationCreateStructs = [], ?array $fieldIdentifiersToValidate = null): Content;

    /**
     * Updates the metadata.
     *
     * (see {@link ContentMetadataUpdateStruct}) of a content object - to update fields use updateContent
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to update the content meta data
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the remoteId in $contentMetadataUpdateStruct is set but already exists
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentMetadataUpdateStruct $contentMetadataUpdateStruct
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content the content with the updated attributes
     */
    public function updateContentMetadata(ContentInfo $contentInfo, ContentMetadataUpdateStruct $contentMetadataUpdateStruct): Content;

    /**
     * Deletes a content object including all its versions and locations including their subtrees.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to delete the content (in one of the locations of the given content object)
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     *
     * @return int[] Affected Location Id's (List of Locations of the Content that was deleted)
     */
    public function deleteContent(ContentInfo $contentInfo): iterable;

    /**
     * Creates a draft from a published or archived version.
     *
     * If no version is given, the current published version is used.
     * 4.x: The draft is created with the initialLanguage code of the source version or if not present with the main language.
     * It can be changed on updating the version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the current-user is not allowed to create the draft
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo|null $versionInfo
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User|null $creator Used as creator of the draft if given - otherwise uses current-user
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Language|null if not set the draft is created with the initialLanguage code of the source version or if not present with the main language.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content - the newly created content draft
     */
    public function createContentDraft(
        ContentInfo $contentInfo,
        ?VersionInfo $versionInfo = null,
        ?User $creator = null,
        ?Language $language = null
    ): Content;

    /**
     * Counts drafts for a user.
     *
     * If no user is given the number of drafts for the authenticated user are returned
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user The user to load drafts for, if defined, otherwise drafts for current-user
     *
     * @return int The number of drafts ({@link VersionInfo}) owned by the given user
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function countContentDrafts(?User $user = null): int;

    /**
     * Loads drafts for a user.
     *
     * If no user is given the drafts for the authenticated user are returned
     *
     * @deprecated Please use {@see loadContentDraftList()} instead to avoid risking loading too much data.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the current-user is not allowed to load the draft list
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user The user to load drafts for, if defined, otherwise drafts for current-user
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo[] the drafts ({@link VersionInfo}) owned by the given user
     */
    public function loadContentDrafts(?User $user = null): iterable;

    /**
     * Loads drafts for a user when content is not in the trash. The list is sorted by modification date.
     *
     * If no user is given the drafts for the authenticated user are returned
     *
     * @since 7.5.5
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User|null $user The user to load drafts for, if defined, otherwise drafts for current-user
     * @param int $offset
     * @param int $limit
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentDraftList
     */
    public function loadContentDraftList(?User $user = null, int $offset = 0, int $limit = -1): ContentDraftList;

    /**
     * Updates the fields of a draft.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to update this version
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the version is not a draft
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException if a field in the $contentUpdateStruct is not valid
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException if a required field is set to an empty value
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if a field value is not accepted by the field type
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentUpdateStruct $contentUpdateStruct
     * @param string[]|null $fieldIdentifiersToValidate List of field identifiers for partial validation or null
     *                      for case of full validation. Empty identifiers array is equal to no validation.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content the content draft with the updated fields
     */
    public function updateContent(VersionInfo $versionInfo, ContentUpdateStruct $contentUpdateStruct, ?array $fieldIdentifiersToValidate = null): Content;

    /**
     * Publishes a content version.
     *
     * Publishes a content version and deletes archive versions if they overflow max archive versions.
     * Max archive versions are currently a configuration for default max limit, by default set to 5.
     *
     * @todo Introduce null|int ContentType->versionArchiveLimit to be able to let admins override this per type.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to publish this version
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the version is not a draft
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo
     * @param string[] $translations List of language codes of translations which will be included
     *                               in a published version.
     *                               By default all translations from the current version will be published.
     *                               If the list is provided but does not cover all currently published translations,
     *                               the missing ones will be copied from the currently published version,
     *                               overriding those in the current version.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    public function publishVersion(VersionInfo $versionInfo, array $translations = Language::ALL): Content;

    /**
     * Removes the given version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the version is in
     *         published state or is a last version of Content in non draft state
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to remove this version
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo
     */
    public function deleteVersion(VersionInfo $versionInfo): void;

    /**
     * Loads all versions for the given content.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to list versions
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the given status is invalid
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     * @param int|null $status
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo[] an array of {@link \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo} sorted by creation date
     */
    public function loadVersions(ContentInfo $contentInfo, ?int $status = null): iterable;

    /**
     * Copies the content to a new location. If no version is given,
     * all versions are copied, otherwise only the given version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to copy the content to the given location
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct $destinationLocationCreateStruct the target location where the content is copied to
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    public function copyContent(ContentInfo $contentInfo, LocationCreateStruct $destinationLocationCreateStruct, ?VersionInfo $versionInfo = null): Content;

    /**
     * Loads all outgoing relations for the given version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to read this version
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation[]
     */
    public function loadRelations(VersionInfo $versionInfo): iterable;

    /**
     * Counts all incoming relations for the given content object.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     *
     * @return int The number of reverse relations ({@link Relation})
     */
    public function countReverseRelations(ContentInfo $contentInfo): int;

    /**
     * Loads all incoming relations for a content object.
     *
     * The relations come only from published versions of the source content objects
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to read this version
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation[]
     */
    public function loadReverseRelations(ContentInfo $contentInfo): iterable;

    /**
     * Loads all incoming relations for a content object.
     *
     * The relations come only from published versions of the source content objects.
     * If the user is not allowed to read specific version then UnauthorizedRelationListItem is returned
     * {@link \Ibexa\Contracts\Core\Repository\Values\Content\RelationList\Item\UnauthorizedRelationListItem}
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     * @param int $offset
     * @param int $limit
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\RelationList
     */
    public function loadReverseRelationList(ContentInfo $contentInfo, int $offset = 0, int $limit = -1): RelationList;

    /**
     * Adds a relation of type common.
     *
     * The source of the relation is the content and version
     * referenced by $versionInfo.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed to edit this version
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the version is not a draft
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $sourceVersion
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $destinationContent the destination of the relation
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation the newly created relation
     */
    public function addRelation(VersionInfo $sourceVersion, ContentInfo $destinationContent): Relation;

    /**
     * Removes a relation of type COMMON from a draft.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed edit this version
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the version is not a draft
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if there is no relation of type COMMON for the given destination
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $sourceVersion
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $destinationContent
     */
    public function deleteRelation(VersionInfo $sourceVersion, ContentInfo $destinationContent): void;

    /**
     * Delete Content item Translation from all Versions (including archived ones) of a Content Object.
     *
     * NOTE: this operation is risky and permanent, so user interface should provide a warning before performing it.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the specified Translation
     *         is the Main Translation of a Content Item.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed
     *         to delete the content (in one of the locations of the given Content Item).
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if languageCode argument
     *         is invalid for the given content.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     * @param string $languageCode
     *
     * @since 6.13
     */
    public function deleteTranslation(ContentInfo $contentInfo, string $languageCode): void;

    /**
     * Delete specified Translation from a Content Draft.
     *
     * When using together with ContentService::publishVersion() method, make sure to not provide deleted translation
     * in translations array, as it is going to be copied again from published version.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the specified Translation
     *         is the only one the Content Draft has or it is the main Translation of a Content Object.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the user is not allowed
     *         to edit the Content (in one of the locations of the given Content Object).
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if languageCode argument
     *         is invalid for the given Draft.
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if specified Version was not found
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo Content Version Draft
     * @param string $languageCode Language code of the Translation to be removed
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content Content Draft w/o the specified Translation
     *
     * @since 6.12
     */
    public function deleteTranslationFromDraft(VersionInfo $versionInfo, string $languageCode): Content;

    /**
     * Hides Content by making all the Locations appear hidden.
     * It does not persist hidden state on Location object itself.
     *
     * Content hidden by this API can be revealed by revealContent API.
     *
     * @see revealContent
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     */
    public function hideContent(ContentInfo $contentInfo): void;

    /**
     * Reveals Content hidden by hideContent API.
     * Locations which were hidden before hiding Content will remain hidden.
     *
     * @see hideContent
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     */
    public function revealContent(ContentInfo $contentInfo): void;

    /**
     * Instantiates a new content create struct object.
     *
     * alwaysAvailable is set to the ContentType's defaultAlwaysAvailable
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType $contentType
     * @param string $mainLanguageCode
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct
     */
    public function newContentCreateStruct(ContentType $contentType, string $mainLanguageCode): ContentCreateStruct;

    /**
     * Instantiates a new content meta data update struct.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentMetadataUpdateStruct
     */
    public function newContentMetadataUpdateStruct(): ContentMetadataUpdateStruct;

    /**
     * Instantiates a new content update struct.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentUpdateStruct
     */
    public function newContentUpdateStruct(): ContentUpdateStruct;

    /**
     * Validates given content related ValueObject returning field errors structure as a result.
     *
     * @param array $context Additional context parameters to be used by validators.
     * @param string[]|null $fieldIdentifiersToValidate List of field identifiers for partial validation or null
     *                      for case of full validation. Empty identifiers array is equal to no validation.
     *
     * @return array Validation errors grouped by field definition and language code, in format:
     *           $returnValue[string|int $fieldDefinitionId][string $languageCode] = $fieldErrors;
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function validate(ValueObject $object, array $context, ?array $fieldIdentifiersToValidate = null): array;

    /**
     * Fetch Content items from the Repository filtered by the given conditions.
     *
     * @param string[] $languages a list of language codes to be added as additional constraints.
     *        If skipped, by default, unless SiteAccessAware layer has been disabled, languages set
     *        for a SiteAccess in a current context will be used.
     */
    public function find(Filter $filter, ?array $languages = null): ContentList;
}

class_alias(ContentService::class, 'eZ\Publish\API\Repository\ContentService');
