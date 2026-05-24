<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Topology\Export;

use BabelForge\MemberGraph\Application\Topology\MemberGraphTopology;

/**
 * Exports a member graph topology to a concrete representation.
 *
 * @template-covariant TExport
 */
interface MemberGraphTopologyExporterInterface
{
    /**
     * Exports the given topology.
     *
     * @param MemberGraphTopology $topology the topology to export
     *
     * @return TExport
     */
    public function export(MemberGraphTopology $topology): mixed;
}
