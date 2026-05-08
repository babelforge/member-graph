<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology\Export;

use JsonException;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopology;

/**
 * Exports member graph topology DTOs to JSON.
 *
 * @implements MemberGraphTopologyExporterInterface<string>
 */
final readonly class MemberGraphTopologyJsonExporter implements MemberGraphTopologyExporterInterface
{
    /**
     * Constructor.
     *
     * @param MemberGraphTopologyArrayExporter $arrayExporter The canonical array exporter.
     * @param int $jsonFlags The json_encode flags.
     */
    public function __construct(
        private MemberGraphTopologyArrayExporter $arrayExporter = new MemberGraphTopologyArrayExporter(),
        private int $jsonFlags = JSON_THROW_ON_ERROR,
    ) {
    }

    /**
     * Exports the given topology to JSON.
     *
     * @param MemberGraphTopology $topology The topology to export.
     *
     * @return string
     *
     * @throws JsonException When JSON encoding fails.
     */
    public function export(MemberGraphTopology $topology): string
    {
        $json = json_encode($this->arrayExporter->export($topology), $this->jsonFlags);

        if (false === $json) {
            throw new JsonException(json_last_error_msg(), json_last_error());
        }

        return $json;
    }
}
