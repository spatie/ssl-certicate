<?php

namespace Spatie\SslCertificate\Test;

use PHPUnit\Framework\TestCase;
use Spatie\SslCertificate\Exceptions\InvalidUrl;
use Spatie\SslCertificate\Url;

class UrlTest extends TestCase
{
    /** @test */
    public function it_can_determine_a_host_name()
    {
        $url = new Url('https://spatie.be/opensource');

        $this->assertSame('spatie.be', $url->getHostName());
    }

    /** @test */
    public function it_can_determine_a_host_name_when_not_specifying_a_protocol()
    {
        $url = new Url('spatie.be');

        $this->assertSame('spatie.be', $url->getHostName());
    }

    /** @test */
    public function it_throws_an_exception_when_creating_an_url_from_an_empty_string()
    {
        $this->expectException(InvalidUrl::class);

        new Url('');
    }

    /** @test */
    public function it_can_assume_a_default_port_when_not_explicitly_defined()
    {
        $url = new Url('spatie.be');

        $this->assertSame(443, $url->getPort());
    }

    /** @test */
    public function it_can_retrieve_the_custom_port_when_defined()
    {
        $url = new Url('https://spatie.be:12345');

        $this->assertSame(12345, $url->getPort());
    }

    /** @test */
    public function it_can_parse_really_long_paths()
    {
        $url = new Url('https://random.host/this-is-a-very/and-i-mean-very/long-path/to-work/with/and-somehow/the-idna-functions-in-php/are-limited-to/61-chars/yes?really=true&ohmy');

        $this->assertSame('random.host', $url->getHostName());
    }
}
