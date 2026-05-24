<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Topology\Export;

use BabelForge\MemberGraph\Application\Topology\MemberGraphTopology;

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
     * @param MemberGraphTopologyArrayExporter $arrayExporter the canonical array exporter
     * @param int                              $jsonFlags     the json_encode flags
     */
    public function __construct(
        private MemberGraphTopologyArrayExporter $arrayExporter = new MemberGraphTopologyArrayExporter(),
        private int $jsonFlags = JSON_THROW_ON_ERROR,
    ) {
    }

    /**
     * Exports the given topology to JSON.
     *
     * @param MemberGraphTopology $topology the topology to export
     *
     * @throws \JsonException when JSON encoding fails
     */
    public function export(MemberGraphTopology $topology): string
    {
        $json = json_encode($this->arrayExporter->export($topology), $this->jsonFlags);

        if (false === $json) {
            throw new \JsonException(json_last_error_msg(), json_last_error());
        }

        return $json;
    }
}
