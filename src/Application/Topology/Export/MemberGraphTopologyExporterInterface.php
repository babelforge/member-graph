<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology\Export;

use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopology;

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
     * @param MemberGraphTopology $topology The topology to export.
     *
     * @return TExport
     */
    public function export(MemberGraphTopology $topology): mixed;
}
