<?php

declare(strict_types=1);

namespace Asad\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use League\OAuth2\Client\Token\AccessToken;
use Asad\OAuth2\Client\AccessToken\ZohoAccessToken;
use InvalidArgumentException;

class Zoho extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * You must use your domain-specific Zoho Accounts URL to generate access and refresh tokens.
     * The following are the various domains and their corresponding accounts URLs.
     */

    private array $dcDomain = [
        'US' => 'https://accounts.zoho.com',
        'AU' => 'https://accounts.zoho.com.au',
        'EU' => 'https://accounts.zoho.eu',
        'IN' => 'https://accounts.zoho.in',
        'CN' => 'https://accounts.zoho.com.cn',
        'JP' => 'https://accounts.zoho.jp',
        'CA' => 'https://accounts.zohocloud.ca',
        'SA' => 'https://accounts.zoho.sa',
    ];

    /**
     * @var string define which data center you want to use (defaults to US)
     * @link https://www.zoho.com/crm/developer/docs/api/multi-dc.html
     */
    protected string $dc = 'US';

    /**
     * @param array $options
     * @param array $collaborators
     *
     * @throws InvalidArgumentException if the given data centre is not supported
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        $this->dc = strtoupper($this->dc);

        if (!isset($this->dcDomain[$this->dc])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown Zoho data centre "%s". Supported data centres are: %s.',
                $this->dc,
                implode(', ', array_keys($this->dcDomain))
            ));
        }
    }

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl(): string
    {
        return $this->getDcDomain() . '/oauth/v2/auth';
    }

    /**
     * Get access token url to retrieve token
     *
     * @param array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->getDcDomain() . '/oauth/v2/token';
    }

    /**
     * Get provider url to fetch user details
     *
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->getDcDomain() . '/oauth/user/info';
    }

    /**
     * @link https://www.zoho.com/crm/developer/docs/api/oauth-overview.html#scopes
     * The provided scope will be used if you don't give any scope
     * and this scope will be used to grab user accounts public information
     *
     * @return array
     */
    protected function getDefaultScopes(): array
    {
        return ['aaaserver.profile.READ'];
    }

    /**
     * Returns the string that should be used to separate scopes when building
     * the URL for requesting an access token.
     *
     * @return string Scope separator, defaults to ','
     */
    protected function getScopeSeparator(): string
    {
        return ',';
    }

    /**
     * Check a provider response for errors.
     *
     * @param  ResponseInterface $response
     * @param  array|string $data
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        // @codeCoverageIgnoreStart
        if (empty($data['error'])) {
            return;
        }
        // @codeCoverageIgnoreEnd

        $error = $data['error'];
        throw new IdentityProviderException(
            $error,
            $response->getStatusCode(),
            $response
        );
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     *
     * @return ZohoUser
     */
    protected function createResourceOwner(array $response, AccessToken $token): ZohoUser
    {
        return new ZohoUser($response);
    }

    /**
     * Creates an access token from a response.
     *
     * The grant that was used to fetch the response can be used to provide
     * additional context.
     *
     * @param  array $response
     * @param  AbstractGrant $grant
     * @return AccessTokenInterface
     */
    protected function createAccessToken(array $response, AbstractGrant $grant): AccessTokenInterface
    {
        return new ZohoAccessToken($response);
    }

    /**
     * You must use your domain-specific Zoho Accounts URL for every leg of the
     * flow: authorization, token exchange and user info. The constructor has
     * already checked that the data centre is one we know about.
     *
     * @return string zoho data center url
     */
    private function getDcDomain(): string
    {
        return $this->dcDomain[$this->dc];
    }
}
