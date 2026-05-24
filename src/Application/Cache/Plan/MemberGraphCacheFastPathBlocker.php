<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Plan;

/**
 * Enumerates cache diagnostics that prevent fast-path reuse or future partial rebuild planning.
 */
enum MemberGraphCacheFastPathBlocker
{
    case NO_SCANNED_FILES;
    case STALE_FILES;
    case DELETED_FILES;
    case MISSING_FILE_PAYLOADS;
    case MISSING_GRAPH_FRAGMENTS;
    case MISSING_KNOWN_OWNERS;
    case MISSING_VIRTUAL_FILE_REFERENCES;
    case MISSING_GLOBAL_INDEX_INPUT_SNAPSHOT;
    case INCOMPATIBLE_GLOBAL_INDEX_INPUT_SNAPSHOT;
    case MISSING_DECLARATION_SNAPSHOT;
}
