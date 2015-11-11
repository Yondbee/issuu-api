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

use GuzzleHttp\Client;
use GuzzleHttp\Post\PostFile;

class Issuu
{
    const ORIGIN_API_UPLOAD = 'apiupload';
    const ORIGIN_API_SLURP = 'apislurp';
    const ORIGIN_SINGLE_UPLOAD = 'singleupload';
    const ORIGIN_MULTI_UPLOAD = 'multiupload';
    const ORIGIN_SINGLE_SLURP = 'singleslurp';
    const ORIGIN_MULTI_SLURP = 'multislurp';
    const ORIGIN_AUTO_SLURP = 'autoslurp';
    const ORIGIN_ANY = null;

    const ORIGINAL_DOCTYPE_PDF = 'pdf';
    const ORIGINAL_DOCTYPE_ODT = 'odt';
    const ORIGINAL_DOCTYPE_DOC = 'doc';
    const ORIGINAL_DOCTYPE_WPD = 'wpd';
    const ORIGINAL_DOCTYPE_SXW = 'sxw';
    const ORIGINAL_DOCTYPE_SXI = 'sxi';
    const ORIGINAL_DOCTYPE_RTF = 'rtf';
    const ORIGINAL_DOCTYPE_ODP = 'odp';
    const ORIGINAL_DOCTYPE_PPT = 'ppt';

    const RESULT_ORDER_ASC = 'asc';
    const RESULT_ORDER_DESC = 'desc';

    const DOC_PARAM_USERNAME = 'username';
    const DOC_PARAM_NAME = 'name';
    const DOC_PARAM_ID = 'documentId';
    const DOC_PARAM_TITLE = 'title';
    const DOC_PARAM_ACCESS = 'access';
    const DOC_PARAM_STATE = 'state';
    const DOC_PARAM_ERROR_CODE = 'errorCode';
    const DOC_PARAM_CATEGORY = 'category';
    const DOC_PARAM_TYPE = 'type';
    const DOC_PARAM_ORIGINAL_TYPE = 'orgDocType';
    const DOC_PARAM_ORIGINAL_NAME = 'orgDocName';
    const DOC_PARAM_ORIGIN = 'origin';
    const DOC_PARAM_LANGUAGE = 'language';
    const DOC_PARAM_PAGE_COUNT = 'pageCount';
    const DOC_PARAM_PUBLISH_DATE = 'publishDate';
    const DOC_PARAM_DESCRIPTION = 'description';
    const DOC_PARAM_TAGS = 'tags';
    const DOC_PARAM_WARNINGS = 'warnings';
    const DOC_PARAM_FOLDERS = 'folders';

    const FIND_PARAM_STATES = 'documentStates';
    const FIND_PARAM_ACCESS = 'access';
    const FIND_PARAM_ORIGINS = 'origins';
    const FIND_PARAM_ORIGINAL_TYPE = 'orgDocType';
    const FIND_PARAM_ORIGINAL_NAME = 'orgDocName';
    const FIND_PARAM_RESULT_ORDER = 'resultOrder';
    const FIND_PARAM_START_INDEX = 'startIndex';
    const FIND_PARAM_PAGE_SIZE = 'pageSize';
    const FIND_PARAM_SORT_BY = 'documentSortBy';
    const FIND_PARAM_RESPONSE_PARAMS = 'responseParams';

    const ERROR_AUTH_REQUIRED = 9;
    const ERROR_INVALID_APIKEY = 10;
    const ERROR_REQUIRED_FIELD_MISSING = 200;
    const ERROR_INVALID_FIELD_FORMAT = 201;
    const ERROR_DOCUMENT_NOT_FOUND = 300;
    const ERROR_DOCUMENT_STILL_CONVERTING = 307;
    const ERROR_DOCUMENT_FAILED_CONVERSION = 308;

    protected $key;
    protected $secret;
    protected $client;

    public function __construct($key, $secret, $proxy = null)
    {
        $this->key = $key;
        $this->secret = $secret;

        $parameters = array(
            'defaults' => array(
                'expect' => false
            )
        );

        if ($proxy) {
            $parameters['defaults']['proxy'] = $proxy;
        }

        $this->client = new Client($parameters);
    }

    private function doAction(
        $actionName,
        array $options,
        $endPoint = 'http://api.issuu.com/1_0'
    ) {
        $header = array(
            'action'      => $actionName,
            'apiKey'      => $this->key,
            'format'      => 'json',
        );

        $data = array_merge($header, $options);

        // Normalize data
        array_walk($data, function (&$value, $key) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_array($value)) {
                $value = implode(',', $value);
            }
        });

        // Remove null fields
        $data = array_filter($data, function ($data) {
            return $data !== null;
        });

        // Create signature
        $fields = array_keys($data);
        sort($fields);

        $signature = $this->secret;
        foreach ($fields as $field) {
            if (is_string($data[$field]) || is_numeric($data[$field])) {
                $signature .= $field.$data[$field];
            } elseif (is_bool($data[$field])) {
                $data[$field] = $data[$field] ? 'true' : 'false';
                $signature .= $field.$data[$field];
            }
        }

        $data['signature'] = md5($signature);

        $response = $this->client->post(
            $endPoint,
            array('body' => $data)
        );

        $response = $response->json();
        if ($response['rsp']['stat'] == 'fail') {
            $message = $response['rsp']['_content']['error']['message'];
            if (isset($response['rsp']['_content']['error']['field'])) {
                $message .= ': '.$response['rsp']['_content']['error']['field'];
            }
            $code = intval($response['rsp']['_content']['error']['code']);

            throw new IssuuException($message, $code);
        }

        return $response['rsp'];
    }

    protected $documents = null;

    public function getDocuments()
    {
        if ($this->documents === null) {

            $response = $this->doAction(
                'issuu.documents.list',
                array(
                    'pageSize' => 0
                )
            );

            $this->documents = array();

            $totalCount = $response['_content']['result']['totalCount'];
            $pageSize = 100;

            for ($startIndex = 0; $startIndex < $totalCount; $startIndex += $pageSize) {
                $response = $this->doAction(
                    'issuu.documents.list',
                    array(
                        'startIndex' => $startIndex,
                        'pageSize' => $pageSize
                    )
                );

                foreach ($response['_content']['result']['_content'] as $item) {
                    $this->documents[] = new Document($item['document']);
                }

                if ($response['_content']['result']['more']) {
                    sleep(1);
                }
            }
        }

        return $this->documents;
    }

    public function upload(array $options)
    {
        $response = $this->doAction(
            'issuu.document.upload',
            $options,
            'http://upload.issuu.com/1_0'
        );

        return $response['_content']['document'];
    }
    
    public function url_upload(array $options)
    {
        $response = $this->doAction(
            'issuu.document.url_upload',
            $options
        );

        return $response['_content']['document'];
    }    

    public function update(array $options)
    {
        $response = $this->doAction(
            'issuu.document.update',
            $options
        );

        return $response['_content']['document'];
    }

    public function delete(array $names)
    {
        $response = $this->doAction(
            'issuu.document.delete',
            array(
                'names' => implode(',', $names)
            )
        );
    }

    public function find(array $options = array())
    {
        $response = $this->doAction(
            'issuu.documents.list',
            $options
        );

        $response = $response['_content']['result'];
        $documents = $response['_content'];
        $response['documents'] = $documents;
        unset($response['_content']);

        return $response;
    }

    public function addEmbed(
        $documentId,
        $readerStartPage = 1,
        $width = 640,
        $height = 480
    ) {
        $response = $this->doAction(
            'issuu.document_embed.add',
            array(
                'documentId' => $documentId,
                'readerStartPage' => $readerStartPage,
                'width' => $width,
                'height' => $height
            )
        );

        return $response['_content']['documentEmbed'];
    }

    public function updateEmbed(
        $documentId,
        $readerStartPage = 1,
        $width = 640,
        $height = 480
    ) {
        $response = $this->doAction(
            'issuu.document_embed.update',
            array(
                'documentId' => $documentId,
                'readerStartPage' => $readerStartPage,
                'width' => $width,
                'height' => $height
            )
        );

        return $response['_content']['documentEmbed'];
    }
}
