<?php

namespace SaintSystems\OData\Tests\Core;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testIsUuid()
    {
        $this->assertTrue(
            is_uuid('4291e9f7-dea1-eb11-b1ac-000d3ab7a7ea'),
            'Normal UUID'
        );
        $this->assertTrue(
            is_uuid('d9737e50-dad9-5b02-268b-4ddcf570108c'),
            'Microsoft Dynamics CRM generated previously invalid UUID'
        );
        $this->assertFalse(
            is_uuid('!4291e9f7-dea1-eb11-b1ac-000d3ab7a7eaLOL'),
            'Invalid prefix and suffix'
        );
    }
}
