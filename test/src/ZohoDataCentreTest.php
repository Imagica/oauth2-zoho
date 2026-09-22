<?php

declare(strict_types=1);

namespace Asad\OAuth2\Client\Test;

use Asad\OAuth2\Client\AccessToken\ZohoAccessToken;
use Asad\OAuth2\Client\Provider\Zoho;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ZohoDataCentreTest extends TestCase
{
    /**
     * Every leg of the flow has to stay on the same accounts domain. Splitting
     * them sends the user to one cloud to authorize and redeems the code in
     * another, which the sovereign clouds (CA, SA) reject outright.
     */
    public function dataCentreProvider(): array
    {
        return [
            'US' => ['US', 'accounts.zoho.com'],
            'AU' => ['AU', 'accounts.zoho.com.au'],
            'EU' => ['EU', 'accounts.zoho.eu'],
            'IN' => ['IN', 'accounts.zoho.in'],
            'CN' => ['CN', 'accounts.zoho.com.cn'],
            'JP' => ['JP', 'accounts.zoho.jp'],
            'CA' => ['CA', 'accounts.zohocloud.ca'],
            'SA' => ['SA', 'accounts.zoho.sa'],
        ];
    }

    /**
     * @dataProvider dataCentreProvider
     */
    public function testAllUrlsUseTheSelectedDataCentre(string $dc, string $expectedHost): void
    {
        $provider = $this->provider(['dc' => $dc]);
        $token = new ZohoAccessToken(['access_token' => 'mock_access_token']);

        $this->assertSame($expectedHost, $this->host($provider->getAuthorizationUrl()));
        $this->assertSame($expectedHost, $this->host($provider->getBaseAccessTokenUrl([])));
        $this->assertSame($expectedHost, $this->host($provider->getResourceOwnerDetailsUrl($token)));
    }

    public function testDefaultsToUsWhenNoDataCentreIsGiven(): void
    {
        $provider = $this->provider();

        $this->assertSame('accounts.zoho.com', $this->host($provider->getAuthorizationUrl()));
        $this->assertSame('accounts.zoho.com', $this->host($provider->getBaseAccessTokenUrl([])));
    }

    public function testDataCentreIsCaseInsensitive(): void
    {
        $provider = $this->provider(['dc' => 'au']);

        $this->assertSame('accounts.zoho.com.au', $this->host($provider->getBaseAccessTokenUrl([])));
    }

    public function testUnknownDataCentreIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown Zoho data centre "AUS"');

        $this->provider(['dc' => 'AUS']);
    }

    private function provider(array $options = []): Zoho
    {
        return new Zoho($options + [
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    private function host(string $url): string
    {
        return parse_url($url, PHP_URL_HOST);
    }
}
