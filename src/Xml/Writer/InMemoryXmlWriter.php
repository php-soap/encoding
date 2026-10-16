<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Throwable;
use VeeWee\Xml\Exception\RuntimeException;
use XMLWriter;
use function count;
use function VeeWee\Xml\ErrorHandling\issue_collection_from_xml_errors;

/**
 * Runs once per encoded element, so it drives XMLWriter directly: cheaper than VeeWee\Xml\Writer\Writer,
 * while throwing the same exceptions as Writer::write() with disallow_issues().
 */
final class InMemoryXmlWriter
{
    /**
     * @param callable(XMLWriter): Generator<bool> $writer
     *
     * @throws RuntimeException
     *
     * @return non-empty-string
     */
    public function __invoke(callable $writer): string
    {
        $xmlWriter = XMLWriter::toMemory();

        $previousErrorReporting = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $failure = null;
        try {
            foreach ($writer($xmlWriter) as $written) {
                if ($written === false) {
                    throw RuntimeException::withMessage('Could not write the provided XML to the stream.');
                }
            }
        } catch (Throwable $exception) {
            $failure = $exception;
        }

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorReporting);

        if ($failure !== null) {
            throw RuntimeException::combineExceptionWithIssues($failure, issue_collection_from_xml_errors($errors));
        }

        if ($errors) {
            $issues = issue_collection_from_xml_errors($errors);
            if (count($issues)) {
                throw RuntimeException::fromIssues('XML issues detected: ', $issues);
            }
        }

        /** @psalm-var non-empty-string */
        return $xmlWriter->outputMemory();
    }
}
