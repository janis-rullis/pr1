<?php

namespace App\Helper\RequestBody;

use App\Exception\JsonToArrayException;
use Symfony\Component\HttpFoundation\RequestStack;

class JsonToArray
{
    private $request;
    private $standardizer;
    private $errors;

    public function __construct(RequestStack $requestStack, RequestBodyStandardizer $standardizer)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->standardizer = $standardizer;
        $this->errors = [];
    }

    public function retrieve(): array
    {
        $requestBodyJson = $this->request->getContent();
        $requestBody = json_decode($requestBodyJson, true);
        if (JSON_ERROR_NONE === json_last_error()) {
            $requestBody = $this->standardizer->standardize($requestBody);

            return $requestBody;
        }
        $this->errors['json'] = json_last_error_msg();
        throw new JsonToArrayException($this->errors);
    }
}
