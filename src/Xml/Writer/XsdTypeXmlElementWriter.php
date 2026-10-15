<?php
declare(strict_types=1);

namespace Soap\Encoding\Xml\Writer;

use Generator;
use Soap\Encoding\Encoder\Context;
use Throwable;
use VeeWee\Xml\Exception\RuntimeException;
use XMLWriter;
use function count;
use function VeeWee\Xml\ErrorHandling\issue_collection_from_xml_errors;

final class XsdTypeXmlElementWriter
{
    /**
     * This runs once per encoded element, so it drives XMLWriter itself instead of going through VeeWee\Xml\Writer\Writer.
     * The error handling matches Writer::write() and disallow_issues(): a throw or a false write is wrapped
     * in a RuntimeException together with the libxml issues, and libxml issues on their own fail the write as well.
     *
     * @param callable(XMLWriter): Generator<bool> $children
     *
     * @throws RuntimeException
     *
     * @return non-empty-string
     */
    public function __invoke(Context $context, callable $children): string
    {
        $writer = XMLWriter::toMemory();
        $cursor = (new ElementBuilder($context, $children(...)))($writer);

        $previousErrorReporting = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $failure = null;
        try {
            foreach ($cursor as $written) {
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
        return $writer->outputMemory();
    }
}
