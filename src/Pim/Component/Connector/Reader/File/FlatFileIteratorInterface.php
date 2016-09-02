<?php

namespace Pim\Component\Connector\Reader\File;

/**
 * FileIterator interface to iterate over tabular file formats like CSV and XLSX
 *
 * If there is a need to add supports for an other file format that is not tabular (such as XML or JSON).
 * Do not implement this interface and use an appropriate file iterator for this format in a dedicated reader.
 *
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface FlatFileIteratorInterface extends \Iterator
{
    /**
     * Get directory path. Can be the path of extracted zip archive or directory file path
     *
     * @return string
     */
    public function getDirectoryPath();

    /**
     * Returns file headers
     *
     * @return array
     */
    public function getHeaders();
}
