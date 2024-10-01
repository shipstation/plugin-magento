<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

class Auth
{
    /** @var string|mixed The unique identifier for the type of order source */
    public string $order_source_api_code;
    /** @var string|mixed|null The username of the seller making the request for this order source */
    public ?string $username;
    /** @var string|mixed|null The password of the seller making the request for this order source */
    public ?string $password;
    /** @var string|mixed|null The access token of the seller making the request for this order source */
    public ?string $access_token;
    /** @var string|mixed|null The api key of the seller making the request for this order source */
    public ?string $api_key;
    /** @var string|mixed|null The url of the sellers store, only used in cases where the 3rd party api can be hosted on a seller by seller basis */
    public ?string $url;
    /** @var mixed|null Additional source-specific information needed to connect to the API. */
    public mixed $connection_context;
    /** @var string|mixed|null The name of the connection used, if this order source has */
    public ?string $connection_name;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->order_source_api_code = $data['order_source_api_code'] ?? '';
            $this->username = $data['username'] ?? null;
            $this->password = $data['password'] ?? null;
            $this->access_token = $data['access_token'] ?? null;
            $this->api_key = $data['api_key'] ?? null;
            $this->url = $data['url'] ?? null;
            $this->connection_context = $data['connection_context'] ?? null;
            $this->connection_name = $data['connection_name'] ?? null;
        }
    }
}
