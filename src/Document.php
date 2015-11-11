<?php
/**
 * Evan Issuu
 *
 * An unofficial client for the Issuu API.
 *
 * @author    Tasso Evangelista <tasso@tassoevan.me>
 * @license MIT http://opensource.org/licenses/MIT
 * @php     5.4
 */

namespace Yondbee\APIs\Issuu;

use DateTime;

/**
 * Represents as Issuu document.
 *
 * @package evan/issuu
 */
class Document
{
    const ACCESS_PRIVATE = 'private';
    const ACCESS_PUBLIC = 'public';

    const STATE_ACTIVE = 'A';
    const STATE_FAILED = 'F';
    const STATE_PROCESSING = 'P';

    /**
     * @var string Owner of the document.
     */
    protected $username;

    /**
     * @var string Name of the document.
     */
    protected $name;

    /**
     * @var string ID of the document.
     */
    protected $documentId;

    protected $uploadedAt; // uploadTimestamp
    protected $createdAt; // created
    protected $revisionId;
    protected $publicationId;
    protected $title;
    protected $access;
    protected $state;
    protected $errorCode;
    protected $preview;
    protected $category;
    protected $type;
    protected $orgDocType;
    protected $orgDocName;
    protected $downloadable;
    protected $origin;
    protected $language;
    protected $rating;
    protected $ratingsAllowed;
    protected $ratingDist;
    protected $likes;
    protected $commentsAllowed;
    protected $showDetectedLinks;
    protected $pageCount;
    protected $dcla;
    protected $ep;
    protected $publishDate;
    protected $description;
    protected $tags;
    protected $coverWidth;
    protected $coverHeight;

    public function __construct(array $rawData)
    {
        $this->username = $rawData['username'];
        $this->name = $rawData['name'];
        $this->documentId = $rawData['documentId'];
        $this->uploadedAt = DateTime::createFromFormat('Y-m-d\TH:i:s.???Z', $rawData['uploadTimestamp']) ?: null;
        $this->createdAt = DateTime::createFromFormat('Y-m-d\TH:i:s.???Z', $rawData['created']) ?: null;
        $this->revisionId = $rawData['revisionId'];
        $this->publicationId = $rawData['publicationId'];
        $this->title = $rawData['title'];
        $this->access = $rawData['access'];
        $this->state = $rawData['state'];
        $this->errorCode = $rawData['errorCode'];
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDocumentId()
    {
        return $this->documentId;
    }

    public function getUploadedAt()
    {
        return $this->uploadedAt;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getRevisionId()
    {
        return $this->revisionId;
    }

    public function getPublicationId()
    {
        return $this->publicationId;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getAccess()
    {
        return $this->access;
    }
}
