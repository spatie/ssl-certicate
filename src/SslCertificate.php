<?php

namespace Spatie\SslCertificate;

use Carbon\Carbon;
use Spatie\Macroable\Macroable;

class SslCertificate
{
    use Macroable;

    /** @var array */
    protected $rawCertificateFields = [];

    /** @var string */
    protected $fingerprint = "";

    public static function download(): Downloader
    {
        return new Downloader();
    }

    public static function createForHostName(string $url, int $timeout = 30): self
    {
        $sslCertificate = Downloader::downloadCertificateFromUrl($url, $timeout);

        return $sslCertificate;
    }

    public function __construct(array $rawCertificateFields)
    {
        $this->rawCertificateFields = $rawCertificateFields;
    }

    public function getRawCertificateFields(): array
    {
        return $this->rawCertificateFields;
    }

    public function getIssuer(): string
    {
        return $this->rawCertificateFields['issuer']['CN'] ?? '';
    }

    public function getDomain(): string
    {
        if (! array_key_exists('CN', $this->rawCertificateFields['subject'])) {
            return '';
        }

        /* Common Name is a string */
        if (is_string($this->rawCertificateFields['subject']['CN'])) {
            return $this->rawCertificateFields['subject']['CN'];
        }

        /* Common name is an array consisting of multiple domains, take the first one */
        if (is_array($this->rawCertificateFields['subject']['CN'])) {
            return $this->rawCertificateFields['subject']['CN'][0];
        }

        return '';
    }

    public function getSignatureAlgorithm(): string
    {
        return $this->rawCertificateFields['signatureTypeSN'] ?? '';
    }

    public function getAdditionalDomains(): array
    {
        $additionalDomains = explode(', ', $this->rawCertificateFields['extensions']['subjectAltName'] ?? '');

        return array_map(function (string $domain) {
            return str_replace('DNS:', '', $domain);
        }, $additionalDomains);
    }

    public function validFromDate(): Carbon
    {
        return Carbon::createFromTimestampUTC($this->rawCertificateFields['validFrom_time_t']);
    }

    public function expirationDate(): Carbon
    {
        return Carbon::createFromTimestampUTC($this->rawCertificateFields['validTo_time_t']);
    }

    public function isExpired(): bool
    {
        return $this->expirationDate()->isPast();
    }

    public function isValid(string $url = null)
    {
        if (! Carbon::now()->between($this->validFromDate(), $this->expirationDate())) {
            return false;
        }

        if (! empty($url)) {
            return $this->appliesToUrl($url ?? $this->getDomain());
        }

        return true;
    }

    public function isSelfSigned(): bool
    {
        return $this->getIssuer() === $this->getDomain();
    }

    public function usesSha1Hash(): bool
    {
        $certificateFields = $this->getRawCertificateFields();

        if ($certificateFields['signatureTypeSN'] === 'RSA-SHA1') {
            return true;
        }

        if ($certificateFields['signatureTypeLN'] === 'sha1WithRSAEncryption') {
            return true;
        }

        return false;
    }

    public function isValidUntil(Carbon $carbon, string $url = null): bool
    {
        if ($this->expirationDate()->lte($carbon)) {
            return false;
        }

        return $this->isValid($url);
    }

    public function daysUntilExpirationDate(): int
    {
        $endDate = $this->expirationDate();

        $interval = Carbon::now()->diff($endDate);

        return (int) $interval->format('%r%a');
    }

    public function getDomains(): array
    {
        $allDomains = $this->getAdditionalDomains();

        $uniqueDomains = array_unique($allDomains);

        return array_values(array_filter($uniqueDomains));
    }

    public function appliesToUrl(string $url): bool
    {
        $host = (new Url($url))->getHostName();

        $certificateHosts = $this->getDomains();

        foreach ($certificateHosts as $certificateHost) {
            if ($host === $certificateHost) {
                return true;
            }

            if ($this->wildcardHostCoversHost($certificateHost, $host)) {
                return true;
            }
        }

        return false;
    }

    protected function wildcardHostCoversHost(string $wildcardHost, string $host): bool
    {
        if ($host === $wildcardHost) {
            return true;
        }

        if (! starts_with($wildcardHost, '*')) {
            return false;
        }

        $wildcardHostWithoutWildcard = substr($wildcardHost, 2);

        return substr_count($wildcardHost, '.') >= substr_count($host, '.') && ends_with($host, $wildcardHostWithoutWildcard);
    }

    public function getRawCertificateFieldsJson(): string
    {
        return json_encode($this->getRawCertificateFields());
    }

    public function getHash(): string
    {
        return md5($this->getRawCertificateFieldsJson());
    }

    public function __toString(): string
    {
        return $this->getRawCertificateFieldsJson();
    }

    public function containsDomain(string $domain): bool
    {
        $certificateHosts = $this->getDomains();

        foreach ($certificateHosts as $certificateHost) {
            if ($certificateHost == $domain) {
                return true;
            }

            if (ends_with($domain, '.'.$certificateHost)) {
                return true;
            }
        }

        return false;
    }

    public function setFingerprint(string $fingerprint)
    {
        $this->fingerprint = $fingerprint;
    }

    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }
}
